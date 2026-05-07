<?php

namespace Modules\Tournaments\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;

class TournamentDrawService
{
    public function __construct(private readonly MatchScoreService $scores)
    {
    }

    public function generate(TournamentCategory $category): int
    {
        $category->load('tournament', 'approvedEntrants.players.user.playerProfile');

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
            return $this->roundRobin($category, $entrants);
        }

        return $this->singleElimination($category, $entrants);
    }

    private function singleElimination(TournamentCategory $category, Collection $entrants): int
    {
        $created = 0;

        foreach ($entrants->chunk(2)->values() as $position => $pair) {
            $pair = $pair->values();

            if ($pair->count() < 2) {
                $pair->first()?->forceFill(['draw_position' => ($position * 2) + 1])->save();
                continue;
            }

            $created += $this->createMatch($category, $pair->get(0), $pair->get(1), 1, null, $position + 1);
        }

        return $created;
    }

    private function roundRobin(TournamentCategory $category, Collection $entrants): int
    {
        $created = 0;
        $groups = $entrants->values()->chunk(4)->values();

        foreach ($groups as $groupIndex => $groupEntrants) {
            $groupEntrants = $groupEntrants->values();
            $groupName = 'Group '.chr(65 + $groupIndex);

            foreach ($groupEntrants as $position => $entrant) {
                $entrant->forceFill([
                    'group_name' => $groupName,
                    'draw_position' => $position + 1,
                ])->save();
            }

            for ($i = 0; $i < $groupEntrants->count(); $i++) {
                for ($j = $i + 1; $j < $groupEntrants->count(); $j++) {
                    $created += $this->createMatch($category, $groupEntrants->get($i), $groupEntrants->get($j), 1, $groupName, $created + 1);
                }
            }
        }

        return $created;
    }

    private function createMatch(TournamentCategory $category, TournamentEntrant $sideA, TournamentEntrant $sideB, int $round, ?string $group, int $position): int
    {
        $match = MatchRecord::create([
            'format' => $category->format === 'singles' ? 'singles' : 'doubles',
            'submitted_by' => $category->tournament->organizer_id ?? $sideA->created_by ?? $sideB->created_by,
            'club_id' => $category->tournament->club_id,
            'tournament_id' => $category->tournament_id,
            'tournament_category_id' => $category->id,
            'status' => 'pending_confirmation',
            'played_at' => $category->tournament->starts_at?->toDateString() ?? now()->toDateString(),
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

    private function attachEntrantPlayers(MatchRecord $match, TournamentEntrant $entrant, string $side): void
    {
        foreach ($entrant->players->sortBy('position')->values() as $index => $player) {
            if (! $player->user_id) {
                continue;
            }

            $match->players()->create([
                'user_id' => $player->user_id,
                'side' => $side,
                'position' => $index + 1,
            ]);
        }
    }
}
