<?php

namespace Modules\Tournaments\DrawEngine\Generators;

use Illuminate\Support\Collection;
use Modules\Tournaments\DrawEngine\Contracts\DrawGeneratorInterface;
use Modules\Tournaments\DrawEngine\Enums\StageType;
use Modules\Tournaments\DrawEngine\Services\FeedRuleService;
use Modules\Tournaments\DrawEngine\Services\SeedingService;
use Modules\Tournaments\Models\TournamentCategory;

class DoubleEliminationGenerator implements DrawGeneratorInterface
{
    public function __construct(private readonly SeedingService $seeding, private readonly FeedRuleService $feeds) {}

    public function generate(TournamentCategory $event, Collection $participants, array $settings = []): array
    {
        $slots = $this->seeding->singleEliminationSlots($participants);
        $matches = [];
        $position = 0;

        foreach ($slots->chunk(2) as $pair) {
            $pair = $pair->values();
            $position++;
            $sideA = $pair->get(0);
            $sideB = $pair->get(1);
            $matches[] = [
                'draw_type' => 'double_elimination',
                'stage' => StageType::Winners->value,
                'round' => 1,
                'round_label' => 'Winners round 1',
                'position' => $position,
                'group' => 'Winners',
                'side_a' => $sideA,
                'side_b' => $sideB,
                'participant_ids' => array_values(array_filter([$sideA?->id, $sideB?->id])),
                'is_bye' => ! $sideA || ! $sideB,
                'feed_rule' => null,
            ];
        }

        foreach (range(1, max(1, intdiv($slots->count(), 4))) as $losersPosition) {
            $matches[] = [
                'draw_type' => 'double_elimination',
                'stage' => StageType::Losers->value,
                'round' => 1,
                'round_label' => 'Losers round 1',
                'position' => $losersPosition,
                'group' => 'Losers',
                'side_a' => null,
                'side_b' => null,
                'participant_ids' => [],
                'is_bye' => false,
                'feed_rule' => $this->feeds->doubleEliminationDropRule($losersPosition),
            ];
        }

        return [
            'event' => $event,
            'draw_type' => 'double_elimination',
            'draw_size' => $slots->count(),
            'matches' => $matches,
            'warnings' => ['Losers bracket feed rules are prepared for winner/loser advancement.'],
        ];
    }
}
