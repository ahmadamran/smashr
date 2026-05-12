<?php

namespace Modules\Tournaments\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use RuntimeException;

class TournamentSoftwareImportService
{
    public const TOURNAMENT_ID = 'E4153E6E-E8D5-42AF-9EDC-4B362F00CB2F';

    public const SOURCE_URL = 'https://www.tournamentsoftware.com/tournament/'.self::TOURNAMENT_ID;

    public const PLAYERS_URL = self::SOURCE_URL.'/players';

    public const SNAPSHOT_PATH = 'seeders/data/mss_melaka_badminton_2026_entries.json';

    public const EVENTS = [
        'L12' => ['name' => 'Boys Under 12', 'format' => 'singles', 'level_label' => 'Under 12 Boys'],
        'L15' => ['name' => 'Boys Under 15', 'format' => 'singles', 'level_label' => 'Under 15 Boys'],
        'L18' => ['name' => 'Boys Under 18', 'format' => 'singles', 'level_label' => 'Under 18 Boys'],
        'P12' => ['name' => 'Girls Under 12', 'format' => 'singles', 'level_label' => 'Under 12 Girls'],
        'P15' => ['name' => 'Girls Under 15', 'format' => 'singles', 'level_label' => 'Under 15 Girls'],
        'P18' => ['name' => 'Girls Under 18', 'format' => 'singles', 'level_label' => 'Under 18 Girls'],
    ];

    private array $config = [];

    public function import(?string $playersHtml = null, array $config = []): Tournament
    {
        $previousConfig = $this->config;
        $this->config = $config;

        try {
            $snapshot = $playersHtml === null ? $this->loadSnapshotPayload() : null;
            $players = $playersHtml === null
                ? $this->normalizePlayers($snapshot['players'] ?? [])
                : $this->parsePlayers($playersHtml);
            $sourceMatches = $snapshot['source_matches'] ?? [];

            if ($players->isEmpty()) {
                throw new RuntimeException('TournamentSoftware players page did not expose player rows. Provide/export player data before running this importer.');
            }

            $tournament = $this->upsertTournament();
            $categories = $this->upsertCategories($tournament);
            $usersBySourcePlayerId = collect();

            foreach ($players as $player) {
                $user = $this->upsertPlayer($player);
                $this->syncSchoolClub($user, $player);

                if (filled($player['source_player_id'] ?? null)) {
                    $usersBySourcePlayerId->put((string) $player['source_player_id'], $user);
                }

                foreach ($player['events'] as $eventCode) {
                    if (! isset($categories[$eventCode])) {
                        continue;
                    }

                    $category = $categories[$eventCode];
                    $entrant = $tournament->entrants()->updateOrCreate([
                        'tournament_category_id' => $category->id,
                        'name' => $player['name'],
                    ], [
                        'created_by' => $user->id,
                        'status' => 'approved',
                        'seed' => null,
                    ]);

                    $entrant->players()->updateOrCreate(
                        ['position' => 1],
                        [
                            'user_id' => $user->id,
                            'school_name' => filled($player['school'] ?? null) ? $player['school'] : null,
                        ],
                    );
                }
            }

            $this->resetImportedSinglesRatings($usersBySourcePlayerId);

            foreach ($categories as $eventCode => $category) {
                if (! empty($sourceMatches[$eventCode])) {
                    $this->syncSourceMatches($tournament, $category->fresh(), $sourceMatches[$eventCode], $usersBySourcePlayerId);
                } elseif ($category->approvedEntrants()->count() >= 2) {
                    app(TournamentDrawService::class)->generate($category->fresh());
                }
            }

            return $tournament->fresh('categories.entrants.players.user.playerProfile');
        } finally {
            $this->config = $previousConfig;
        }
    }

    public function parsePlayers(string $html): Collection
    {
        $textRows = collect($this->tableRows($html))
            ->map(fn (string $row) => $this->cleanText($row))
            ->filter(fn (string $row) => $row !== '')
            ->values();

        if ($textRows->isEmpty()) {
            $textRows = str($this->cleanText($html))->explode("\n")->map(fn ($line) => trim($line))->filter()->values();
        }

        return $textRows
            ->map(fn (string $row) => $this->parsePlayerRow($row))
            ->filter()
            ->unique(fn (array $player) => Str::lower($player['name']).'|'.implode(',', $player['events']))
            ->values();
    }

    private function loadSnapshotPayload(): ?array
    {
        $path = database_path($this->snapshotPath());

        if (is_file($path)) {
            return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        }

        return ['players' => $this->parsePlayers($this->fetchPlayersHtml())->all()];
    }

    private function normalizePlayers(array $players): Collection
    {
        return collect($players)
            ->map(function (array $player) {
                $events = collect($player['events'] ?? [])
                    ->map(fn (string $event) => Str::upper($event))
                    ->filter(fn (string $event) => array_key_exists($event, $this->events()))
                    ->unique()
                    ->values()
                    ->all();

                $name = trim((string) ($player['name'] ?? ''));

                if ($name === '' || empty($events)) {
                    return null;
                }

                return [
                    'source_player_id' => isset($player['source_player_id']) ? (string) $player['source_player_id'] : null,
                    'name' => $name,
                    'school' => isset($player['school']) ? trim((string) $player['school']) : null,
                    'events' => $events,
                ];
            })
            ->filter()
            ->unique(fn (array $player) => ($player['source_player_id'] ?: Str::lower($player['name'])).'|'.implode(',', $player['events']))
            ->values();
    }

    private function fetchPlayersHtml(): string
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 Smashr Tournament Importer',
                'Cookie' => 'st=l=1033&c=1',
            ])
            ->get($this->playersUrl());

        if (! $response->ok()) {
            throw new RuntimeException('TournamentSoftware players page returned HTTP '.$response->status().'.');
        }

        return $response->body();
    }

    private function upsertTournament(): Tournament
    {
        $organizer = $this->upsertImportOrganizer();

        return Tournament::updateOrCreate(
            ['slug' => $this->tournamentSlug()],
            [
                'club_id' => null,
                'organizer_id' => $organizer->id,
                'name' => $this->tournamentName(),
                'country' => $this->config['country'] ?? 'Malaysia',
                'state' => $this->config['state'] ?? 'Melaka',
                'city' => $this->config['city'] ?? 'Melaka',
                'venue' => $this->config['venue'] ?? ($this->config['city'] ?? 'Melaka'),
                'starts_at' => $this->config['starts_at'] ?? '2026-05-12',
                'ends_at' => $this->config['ends_at'] ?? '2026-05-14',
                'status' => 'published',
                'registration_mode' => 'private',
                'registration_status' => 'closed',
                'registration_deadline' => $this->config['registration_deadline'] ?? '2026-05-11',
            ],
        );
    }

    private function upsertImportOrganizer(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@smashr.test'],
            [
                'name' => 'Smashr Superadmin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        PlayerProfile::where('user_id', $user->id)
            ->where('slug', 'smashr-superadmin')
            ->delete();

        return $user;
    }

    private function upsertCategories(Tournament $tournament): array
    {
        return collect($this->events())->mapWithKeys(function (array $event, string $code) use ($tournament) {
            $category = $tournament->categories()->updateOrCreate(
                ['slug' => Str::lower($code)],
                [
                    'name' => $event['name'],
                    'format' => $event['format'],
                    'level_label' => $event['level_label'],
                    'draw_mode' => 'single_elimination',
                    'group_size' => 4,
                    'max_entrants' => 128,
                    'status' => 'published',
                ],
            );

            return [$code => $category];
        })->all();
    }

    private function upsertPlayer(array $player): User
    {
        $name = $player['name'];
        $slug = Str::slug($name) ?: 'player';
        $email = $this->playerImportEmail($player);

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->suspended_at = null;

        if (! $user->exists) {
            $user->email_verified_at = now();
            $user->password = Hash::make(Str::random(32));
        } elseif ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        if (! $user->exists || $user->isDirty()) {
            $user->save();
        }

        PlayerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $name,
                'slug' => $this->profileSlugPrefix().$slug,
                'country' => 'Malaysia',
                'state' => $this->config['state'] ?? 'Melaka',
                'city' => $this->config['city'] ?? 'Melaka',
                'gender' => $this->genderFromEvents($player['events'] ?? []),
                'preferred_hand' => 'right',
                'primary_format' => 'singles',
            ],
        );

        return $user;
    }

    private function genderFromEvents(array $events): ?string
    {
        $prefixes = collect($events)
            ->map(fn (string $event) => Str::upper(Str::substr($event, 0, 1)))
            ->unique()
            ->values();

        return match (true) {
            collect($events)->every(fn (string $event) => Str::startsWith(Str::upper($event), 'PL')) => 'male',
            collect($events)->every(fn (string $event) => Str::startsWith(Str::upper($event), 'PP')) => 'female',
            $prefixes->count() === 1 && $prefixes->first() === 'L' => 'male',
            $prefixes->count() === 1 && $prefixes->first() === 'P' => 'female',
            default => null,
        };
    }

    private function syncSchoolClub(User $user, array $player): void
    {
        $school = trim((string) ($player['school'] ?? ''));

        if ($school === '') {
            return;
        }

        $club = Club::whereRaw('lower(name) = ?', [Str::lower($school)])->first();

        if (! $club) {
            $club = Club::create([
                'name' => $school,
                'slug' => $this->uniqueClubSlug(Str::slug($school) ?: 'school'),
                'country' => 'Malaysia',
                'state' => $this->config['state'] ?? 'Melaka',
                'city' => $this->config['city'] ?? 'Melaka',
                'description' => 'School imported from '.$this->tournamentName().' via TournamentSoftware.',
            ]);
        }

        $user->clubs()->syncWithoutDetaching([$club->id]);
    }

    private function uniqueClubSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $count = 2;

        while (Club::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count++;
        }

        return $slug;
    }

    private function syncSourceMatches(Tournament $tournament, TournamentCategory $category, array $sourceMatches, Collection $usersBySourcePlayerId): void
    {
        $scores = app(MatchScoreService::class);

        MatchRecord::where('tournament_category_id', $category->id)->delete();

        foreach ($sourceMatches as $sourceMatch) {
            $this->syncFirstRoundDrawPositions($category, $sourceMatch);
        }

        foreach ($sourceMatches as $sourceMatch) {
            if ($this->isByeOnlySourceMatch($sourceMatch)) {
                continue;
            }

            $score = $sourceMatch['score'] ?? [];
            $shouldApplyRating = $this->shouldApplySourceRating($sourceMatch);
            $scheduledAt = filled($sourceMatch['scheduled_at'] ?? null)
                ? Carbon::parse($sourceMatch['scheduled_at'], 'Asia/Kuala_Lumpur')
                : null;
            $status = ($sourceMatch['status'] ?? null) === 'confirmed' && (! empty($score) || filled($sourceMatch['result_note'] ?? null))
                ? 'confirmed'
                : 'pending_confirmation';

            $match = MatchRecord::create([
                'format' => 'singles',
                'submitted_by' => $tournament->organizer_id,
                'club_id' => $tournament->club_id,
                'tournament_id' => $tournament->id,
                'tournament_category_id' => $category->id,
                'status' => $shouldApplyRating ? 'pending_confirmation' : $status,
                'played_at' => $scheduledAt?->toDateString() ?? $tournament->starts_at?->toDateString() ?? now()->toDateString(),
                'scheduled_at' => $scheduledAt,
                'court_label' => $sourceMatch['court_label'] ?? null,
                'estimated_duration_minutes' => 20,
                'score' => $score,
                'winner_side' => $sourceMatch['winner_side'] ?: 'A',
                'draw_round' => $sourceMatch['draw_round'] ?? null,
                'draw_group' => null,
                'draw_position' => $sourceMatch['draw_position'] ?? null,
                'live_status' => $status === 'confirmed' ? 'approved' : 'scheduled',
                'live_score' => array_replace_recursive($scores->initialLiveScore(), [
                    'current_game' => count($score) + 1,
                    'games' => $score,
                ]),
                'score_submitted_at' => $status === 'confirmed' ? ($scheduledAt ?? now()) : null,
            ]);

            $this->attachSourcePlayer($match, $sourceMatch['side_a_source_player_id'] ?? null, 'A', $usersBySourcePlayerId);
            $this->attachSourcePlayer($match, $sourceMatch['side_b_source_player_id'] ?? null, 'B', $usersBySourcePlayerId);

            if ($shouldApplyRating) {
                app(RatingService::class)->confirmAsAdmin($match->fresh());
            }
        }
    }

    private function resetImportedSinglesRatings(Collection $usersBySourcePlayerId): void
    {
        $userIds = $usersBySourcePlayerId->pluck('id')->all();

        if (empty($userIds)) {
            return;
        }

        PlayerProfile::whereIn('user_id', $userIds)->update([
            'singles_rating' => RatingAlgorithm::DEFAULT_SETTINGS['starting_rating'],
            'singles_matches' => 0,
        ]);
    }

    private function shouldApplySourceRating(array $sourceMatch): bool
    {
        return ($sourceMatch['status'] ?? null) === 'confirmed'
            && ! empty($sourceMatch['score'] ?? [])
            && filled($sourceMatch['winner_side'] ?? null)
            && filled($sourceMatch['side_a_source_player_id'] ?? null)
            && filled($sourceMatch['side_b_source_player_id'] ?? null);
    }

    private function syncFirstRoundDrawPositions(TournamentCategory $category, array $sourceMatch): void
    {
        if ((int) ($sourceMatch['draw_round'] ?? 0) !== 1 || ! filled($sourceMatch['draw_position'] ?? null)) {
            return;
        }

        $positions = [
            'side_a_source_player_id' => (((int) $sourceMatch['draw_position']) * 2) - 1,
            'side_b_source_player_id' => ((int) $sourceMatch['draw_position']) * 2,
        ];

        foreach ($positions as $key => $position) {
            $sourcePlayerId = $sourceMatch[$key] ?? null;
            if (! $sourcePlayerId) {
                continue;
            }

            $email = $this->sourcePlayerEmail($sourcePlayerId);
            $userId = User::where('email', $email)->value('id');
            if (! $userId) {
                continue;
            }

            $category->entrants()
                ->whereHas('players', fn ($players) => $players->where('user_id', $userId))
                ->update(['draw_position' => $position, 'group_name' => null]);
        }
    }

    private function isByeOnlySourceMatch(array $sourceMatch): bool
    {
        $sideCount = collect([
            $sourceMatch['side_a_source_player_id'] ?? null,
            $sourceMatch['side_b_source_player_id'] ?? null,
        ])->filter()->count();

        return $sideCount === 1
            && empty($sourceMatch['score'] ?? [])
            && blank($sourceMatch['scheduled_at'] ?? null);
    }

    private function attachSourcePlayer(MatchRecord $match, ?string $sourcePlayerId, string $side, Collection $usersBySourcePlayerId): void
    {
        if (! $sourcePlayerId || ! $usersBySourcePlayerId->has((string) $sourcePlayerId)) {
            return;
        }

        $match->players()->create([
            'user_id' => $usersBySourcePlayerId->get((string) $sourcePlayerId)->id,
            'side' => $side,
            'position' => 1,
        ]);
    }

    private function playerImportEmail(array $player): string
    {
        $sourcePlayerId = $player['source_player_id'] ?? null;
        $slug = Str::slug($player['name']) ?: 'player';

        return $sourcePlayerId
            ? $this->sourcePlayerEmail($sourcePlayerId)
            : $slug.'-'.$this->tournamentSlug().'@import.smashr.test';
    }

    private function parsePlayerRow(string $row): ?array
    {
        preg_match_all('/\b[LP](?:12|15|18)\b/i', $row, $matches);
        $events = collect($matches[0] ?? [])
            ->map(fn (string $event) => Str::upper($event))
            ->filter(fn (string $event) => array_key_exists($event, $this->events()))
            ->unique()
            ->values()
            ->all();

        if (empty($events)) {
            return null;
        }

        $name = trim(preg_replace('/\b[LP](?:12|15|18)\b/i', ' ', $row));
        $name = trim(preg_replace('/\s+/', ' ', preg_replace('/^\d+\s+/', '', $name)));

        if ($name === '' || Str::contains(Str::lower($name), ['players', 'events', 'draws'])) {
            return null;
        }

        return [
            'name' => $name,
            'events' => $events,
        ];
    }

    private function tableRows(string $html): array
    {
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $html, $matches);

        return $matches[1] ?? [];
    }

    private function cleanText(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);
        $html = preg_replace('/<\/(td|th)>/i', ' ', $html);
        $html = preg_replace('/<\/(tr|p|div|li)>/i', "\n", $html);

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }

    private function events(): array
    {
        return $this->config['events'] ?? self::EVENTS;
    }

    private function playersUrl(): string
    {
        return $this->config['players_url'] ?? self::PLAYERS_URL;
    }

    private function snapshotPath(): string
    {
        return $this->config['snapshot_path'] ?? self::SNAPSHOT_PATH;
    }

    private function tournamentSlug(): string
    {
        return $this->config['slug'] ?? 'mss-melaka-badminton-2026';
    }

    private function tournamentName(): string
    {
        return $this->config['name'] ?? 'MSS MELAKA BADMINTON 2026';
    }

    private function profileSlugPrefix(): string
    {
        return $this->config['profile_slug_prefix'] ?? 'mss-melaka-2026-';
    }

    private function sourcePlayerEmail(string $sourcePlayerId): string
    {
        return ($this->config['email_prefix'] ?? 'ts-e4153-').$sourcePlayerId.'@import.smashr.test';
    }
}
