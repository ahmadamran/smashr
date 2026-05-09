<?php

namespace Modules\Tournaments\Services;

use Illuminate\Support\Collection;
use Modules\Matches\Models\MatchRecord;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;

class RoundRobinStandingsService
{
    public function forGroup(TournamentCategory $category, string $groupName): Collection
    {
        $entrants = $category->entrants
            ->where('status', 'approved')
            ->where('group_name', $groupName)
            ->sortBy('draw_position')
            ->values();

        $rows = $entrants->mapWithKeys(fn (TournamentEntrant $entrant) => [
            $entrant->id => [
                'entrant' => $entrant,
                'played' => 0,
                'won' => 0,
                'lost' => 0,
                'games_for' => 0,
                'games_against' => 0,
                'game_diff' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'point_diff' => 0,
            ],
        ]);

        $category->matches
            ->where('draw_group', $groupName)
            ->where('status', 'confirmed')
            ->each(function (MatchRecord $match) use (&$rows, $entrants) {
                $sideA = $this->entrantForMatchSide($entrants, $match, 'A');
                $sideB = $this->entrantForMatchSide($entrants, $match, 'B');

                if (! $sideA || ! $sideB) {
                    return;
                }

                $sideAGames = 0;
                $sideBGames = 0;
                $sideAPoints = 0;
                $sideBPoints = 0;

                foreach ($match->score ?? [] as $game) {
                    $a = (int) ($game['a'] ?? 0);
                    $b = (int) ($game['b'] ?? 0);
                    $sideAPoints += $a;
                    $sideBPoints += $b;
                    $a > $b ? $sideAGames++ : $sideBGames++;
                }

                $this->applyResult($rows, $sideA->id, $match->winner_side === 'A', $sideAGames, $sideBGames, $sideAPoints, $sideBPoints);
                $this->applyResult($rows, $sideB->id, $match->winner_side === 'B', $sideBGames, $sideAGames, $sideBPoints, $sideAPoints);
            });

        return $rows
            ->values()
            ->sortBy([
                ['won', 'desc'],
                ['game_diff', 'desc'],
                ['point_diff', 'desc'],
                fn (array $a, array $b) => ($a['entrant']->draw_position ?? 9999) <=> ($b['entrant']->draw_position ?? 9999),
            ])
            ->values()
            ->map(function (array $row, int $index) {
                return ['rank' => $index + 1, ...$row];
            });
    }

    private function entrantForMatchSide(Collection $entrants, MatchRecord $match, string $side): ?TournamentEntrant
    {
        $matchUserIds = $match->players->where('side', $side)->pluck('user_id')->sort()->values()->all();

        return $entrants->first(function (TournamentEntrant $entrant) use ($matchUserIds) {
            return $entrant->players->pluck('user_id')->filter()->sort()->values()->all() === $matchUserIds;
        });
    }

    private function applyResult(Collection &$rows, int $entrantId, bool $won, int $gamesFor, int $gamesAgainst, int $pointsFor, int $pointsAgainst): void
    {
        $row = $rows->get($entrantId);
        $row['played']++;
        $won ? $row['won']++ : $row['lost']++;
        $row['games_for'] += $gamesFor;
        $row['games_against'] += $gamesAgainst;
        $row['game_diff'] = $row['games_for'] - $row['games_against'];
        $row['points_for'] += $pointsFor;
        $row['points_against'] += $pointsAgainst;
        $row['point_diff'] = $row['points_for'] - $row['points_against'];
        $rows->put($entrantId, $row);
    }
}
