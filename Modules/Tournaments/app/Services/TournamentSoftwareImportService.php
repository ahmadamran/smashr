<?php

namespace Modules\Tournaments\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Modules\Players\Models\PlayerProfile;
use Modules\Tournaments\Models\Tournament;

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

    public function import(?string $playersHtml = null): Tournament
    {
        $players = $playersHtml === null
            ? $this->loadSnapshotPlayers()
            : $this->parsePlayers($playersHtml);

        if ($players->isEmpty()) {
            throw new RuntimeException('TournamentSoftware players page did not expose player rows. Provide/export player data before running this importer.');
        }

        return DB::transaction(function () use ($players) {
            $tournament = $this->upsertTournament();
            $categories = $this->upsertCategories($tournament);

            foreach ($players as $player) {
                foreach ($player['events'] as $eventCode) {
                    if (! isset($categories[$eventCode])) {
                        continue;
                    }

                    $user = $this->upsertPlayer($player);
                    $category = $categories[$eventCode];
                    $entrant = $tournament->entrants()->updateOrCreate([
                        'tournament_category_id' => $category->id,
                        'name' => $player['name'],
                    ], [
                        'created_by' => $user->id,
                        'status' => 'approved',
                        'seed' => null,
                    ]);

                    $entrant->players()->delete();
                    $entrant->players()->create([
                        'user_id' => $user->id,
                        'position' => 1,
                    ]);
                }
            }

            foreach ($categories as $category) {
                if ($category->approvedEntrants()->count() >= 2) {
                    app(TournamentDrawService::class)->generate($category->fresh());
                }
            }

            return $tournament->fresh('categories.entrants.players.user.playerProfile');
        });
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

    private function loadSnapshotPlayers(): Collection
    {
        $path = database_path(self::SNAPSHOT_PATH);

        if (is_file($path)) {
            $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

            return $this->normalizePlayers($payload['players'] ?? []);
        }

        return $this->parsePlayers($this->fetchPlayersHtml());
    }

    private function normalizePlayers(array $players): Collection
    {
        return collect($players)
            ->map(function (array $player) {
                $events = collect($player['events'] ?? [])
                    ->map(fn (string $event) => Str::upper($event))
                    ->filter(fn (string $event) => array_key_exists($event, self::EVENTS))
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
            ->get(self::PLAYERS_URL);

        if (! $response->ok()) {
            throw new RuntimeException('TournamentSoftware players page returned HTTP '.$response->status().'.');
        }

        return $response->body();
    }

    private function upsertTournament(): Tournament
    {
        return Tournament::updateOrCreate(
            ['slug' => 'mss-melaka-badminton-2026'],
            [
                'club_id' => null,
                'organizer_id' => User::where('email', 'admin@smashr.test')->value('id') ?? User::query()->value('id'),
                'name' => 'MSS MELAKA BADMINTON 2026',
                'country' => 'Malaysia',
                'state' => 'Melaka',
                'city' => 'Melaka',
                'venue' => 'Melaka',
                'starts_at' => '2026-05-12',
                'ends_at' => '2026-05-14',
                'status' => 'published',
                'registration_mode' => 'private',
                'registration_status' => 'closed',
                'registration_deadline' => '2026-05-11',
            ],
        );
    }

    private function upsertCategories(Tournament $tournament): array
    {
        return collect(self::EVENTS)->mapWithKeys(function (array $event, string $code) use ($tournament) {
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
        $sourcePlayerId = $player['source_player_id'] ?? null;
        $slug = Str::slug($name) ?: 'player';
        $email = $sourcePlayerId
            ? 'ts-e4153-'.$sourcePlayerId.'@import.smashr.test'
            : $slug.'-mss-melaka-2026@import.smashr.test';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
                'suspended_at' => null,
            ],
        );

        PlayerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $name,
                'slug' => 'mss-melaka-2026-'.$slug,
                'country' => 'Malaysia',
                'state' => 'Melaka',
                'city' => 'Melaka',
                'preferred_hand' => 'right',
                'primary_format' => 'singles',
            ],
        );

        return $user;
    }

    private function parsePlayerRow(string $row): ?array
    {
        preg_match_all('/\b[LP](?:12|15|18)\b/i', $row, $matches);
        $events = collect($matches[0] ?? [])
            ->map(fn (string $event) => Str::upper($event))
            ->filter(fn (string $event) => array_key_exists($event, self::EVENTS))
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
}
