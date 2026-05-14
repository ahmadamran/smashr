<?php

namespace Modules\Tournaments\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;
use Modules\Matches\Services\MixedDoublesTeamValidator;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;

class TournamentDrawService
{
    public function __construct(private readonly MatchScoreService $scores)
    {
    }

    public function generate(TournamentCategory $category, array $schedule = []): int
    {
        $category->loadMissing('tournament');
        $schedule = $this->normalizeSchedule($category->tournament, $schedule);

        return $this->generateCategory($category, $schedule);
    }

    public function generateTournament(Tournament $tournament, array $categoryIds, array $schedule = []): int
    {
        $categoryIds = collect($categoryIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($categoryIds->isEmpty()) {
            throw ValidationException::withMessages([
                'category_ids' => 'Select at least one category to generate.',
            ]);
        }

        $categories = $tournament->categories()
            ->whereIn('id', $categoryIds)
            ->with('tournament', 'approvedEntrants.players.user.playerProfile')
            ->orderBy('id')
            ->get();

        if ($categories->count() !== $categoryIds->count()) {
            throw ValidationException::withMessages([
                'category_ids' => 'Select categories from this tournament only.',
            ]);
        }

        $schedule = $this->normalizeSchedule($tournament, $schedule);
        $created = 0;

        foreach ($categories as $category) {
            $created += $this->generateCategory($category, $schedule, $created);
        }

        return $created;
    }

    private function generateCategory(TournamentCategory $category, array $schedule, int $sequenceStart = 0): int
    {
        $category->loadMissing('tournament', 'approvedEntrants.players.user.playerProfile');
        $entrants = $category->approvedEntrants
            ->sortBy(fn (TournamentEntrant $entrant) => $entrant->seed ?? 9999)
            ->values();

        if ($entrants->count() < 2) {
            throw ValidationException::withMessages([
                'draw' => 'At least two approved entrants are required to generate a draw.',
            ]);
        }

        MatchRecord::where('tournament_category_id', $category->id)->delete();

        if ($category->draw_mode === 'round_robin') {
            return $this->roundRobin($category, $entrants, $schedule, $sequenceStart);
        }

        return $this->singleElimination($category, $entrants, $schedule, $sequenceStart);
    }

    private function singleElimination(TournamentCategory $category, Collection $entrants, array $schedule, int $sequenceStart): int
    {
        $created = 0;
        $drawSize = $this->nextPowerOfTwo($entrants->count());
        $byeCount = $drawSize - $entrants->count();
        $byeEntrants = $entrants->take($byeCount)->values();
        $playingEntrants = $entrants->slice($byeCount)->values();

        foreach ($byeEntrants as $index => $entrant) {
            $entrant->forceFill([
                'draw_position' => ($index * 2) + 1,
                'group_name' => null,
            ])->save();
        }

        foreach ($playingEntrants->chunk(2)->values() as $index => $pair) {
            $pair = $pair->values();
            $position = $byeCount + $index + 1;
            $created += $this->createMatch($category, $pair->get(0), $pair->get(1), 1, null, $position, $schedule, $sequenceStart + $created);
        }

        return $created;
    }

    private function nextPowerOfTwo(int $count): int
    {
        $drawSize = 2;

        while ($drawSize < max(2, $count)) {
            $drawSize *= 2;
        }

        return $drawSize;
    }

    private function roundRobin(TournamentCategory $category, Collection $entrants, array $schedule, int $sequenceStart): int
    {
        $created = 0;
        $groupSize = min(6, max(3, (int) ($category->group_size ?: 4)));
        $groups = $entrants->values()->chunk($groupSize)->values();

        foreach ($groups as $groupIndex => $groupEntrants) {
            $groupEntrants = $groupEntrants->values();
            $groupName = 'Group '.chr(65 + $groupIndex);

            foreach ($groupEntrants as $position => $entrant) {
                $entrant->forceFill([
                    'group_name' => $groupName,
                    'draw_position' => $position + 1,
                ])->save();
            }

            $groupCreated = 0;
            for ($i = 0; $i < $groupEntrants->count(); $i++) {
                for ($j = $i + 1; $j < $groupEntrants->count(); $j++) {
                    $created += $this->createMatch($category, $groupEntrants->get($i), $groupEntrants->get($j), 1, $groupName, ++$groupCreated, $schedule, $sequenceStart + $created);
                }
            }
        }

        return $created;
    }

    private function createMatch(TournamentCategory $category, TournamentEntrant $sideA, TournamentEntrant $sideB, int $round, ?string $group, int $position, array $schedule, int $sequence): int
    {
        $scheduledAt = $this->scheduledAt($schedule, $sequence);
        $this->validateMixedEntrants($category, $sideA, $sideB);

        $match = MatchRecord::create([
            'format' => $category->format,
            'submitted_by' => $category->tournament->organizer_id ?? $sideA->created_by ?? $sideB->created_by,
            'club_id' => $category->tournament->club_id,
            'tournament_id' => $category->tournament_id,
            'tournament_category_id' => $category->id,
            'status' => 'pending_confirmation',
            'played_at' => $scheduledAt->toDateString(),
            'scheduled_at' => $scheduledAt,
            'court_label' => $this->courtLabel($schedule, $sequence),
            'estimated_duration_minutes' => $schedule['duration_minutes'],
            'score' => [],
            'winner_side' => 'A',
            'draw_round' => $round,
            'draw_group' => $group,
            'draw_position' => $position,
            'live_status' => 'scheduled',
            'live_score' => $this->scores->initialLiveScore(),
        ]);

        $this->scores->ensureScoreSheetToken($match);

        $this->attachEntrantPlayers($match, $sideA, 'A');
        $this->attachEntrantPlayers($match, $sideB, 'B');

        $sideA->forceFill(['draw_position' => ($position * 2) - 1, 'group_name' => $group])->save();
        $sideB->forceFill(['draw_position' => $position * 2, 'group_name' => $group])->save();

        return 1;
    }

    private function normalizeSchedule(Tournament $tournament, array $schedule): array
    {
        $date = $tournament->starts_at?->toDateString() ?? now()->toDateString();

        return [
            'courts_count' => max(1, (int) ($schedule['courts_count'] ?? 1)),
            'court_label_prefix' => trim((string) ($schedule['court_label_prefix'] ?? 'Court')),
            'first_court_number' => max(1, (int) ($schedule['first_court_number'] ?? 1)),
            'start_at' => Carbon::parse($date.' '.($schedule['schedule_start_time'] ?? '09:00')),
            'duration_minutes' => max(5, (int) ($schedule['match_duration_minutes'] ?? 30)),
        ];
    }

    private function scheduledAt(array $schedule, int $sequence): Carbon
    {
        return $schedule['start_at']->copy()->addMinutes(intdiv($sequence, $schedule['courts_count']) * $schedule['duration_minutes']);
    }

    private function courtLabel(array $schedule, int $sequence): string
    {
        $courtNumber = $schedule['first_court_number'] + ($sequence % $schedule['courts_count']);

        return $schedule['court_label_prefix'] === ''
            ? (string) $courtNumber
            : $schedule['court_label_prefix'].' '.$courtNumber;
    }

    private function attachEntrantPlayers(MatchRecord $match, TournamentEntrant $entrant, string $side): void
    {
        foreach ($entrant->players->sortBy('position')->values() as $index => $player) {
            if (! $player->user_id) {
                continue;
            }

            $match->players()->create([
                'user_id' => $player->user_id,
                'club_id' => $this->clubIdForEntrantPlayer($player),
                'side' => $side,
                'position' => $index + 1,
            ]);
        }
    }

    private function validateMixedEntrants(TournamentCategory $category, TournamentEntrant $sideA, TournamentEntrant $sideB): void
    {
        if ($category->format !== 'mixed') {
            return;
        }

        $sideA->loadMissing('players.user.playerProfile');
        $sideB->loadMissing('players.user.playerProfile');

        app(MixedDoublesTeamValidator::class)->validateUserIds(
            'mixed',
            $sideA->players->pluck('user_id')->filter()->all(),
            $sideB->players->pluck('user_id')->filter()->all(),
        );
    }

    private function clubIdForEntrantPlayer($player): ?int
    {
        if (blank($player->school_name)) {
            return null;
        }

        return Club::whereRaw('lower(name) = ?', [Str::lower($player->school_name)])->value('id');
    }
}
