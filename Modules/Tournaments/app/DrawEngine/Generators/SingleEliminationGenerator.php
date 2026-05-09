<?php

namespace Modules\Tournaments\DrawEngine\Generators;

use Illuminate\Support\Collection;
use Modules\Tournaments\DrawEngine\Contracts\DrawGeneratorInterface;
use Modules\Tournaments\DrawEngine\Enums\StageType;
use Modules\Tournaments\DrawEngine\Services\SeedingService;
use Modules\Tournaments\Models\TournamentCategory;

class SingleEliminationGenerator implements DrawGeneratorInterface
{
    public function __construct(private readonly SeedingService $seeding) {}

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
                'draw_type' => 'single_elimination',
                'stage' => StageType::Main->value,
                'round' => 1,
                'round_label' => 'Round 1',
                'position' => $position,
                'group' => null,
                'side_a' => $sideA,
                'side_b' => $sideB,
                'participant_ids' => array_values(array_filter([$sideA?->id, $sideB?->id])),
                'is_bye' => ! $sideA || ! $sideB,
                'feed_rule' => null,
            ];
        }

        return [
            'event' => $event,
            'draw_type' => 'single_elimination',
            'draw_size' => $slots->count(),
            'matches' => $matches,
            'warnings' => [],
        ];
    }
}
