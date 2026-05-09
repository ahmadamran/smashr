<?php

namespace Modules\Tournaments\DrawEngine\Generators;

use Illuminate\Support\Collection;
use Modules\Tournaments\DrawEngine\Contracts\DrawGeneratorInterface;
use Modules\Tournaments\DrawEngine\Enums\StageType;
use Modules\Tournaments\DrawEngine\Services\SeedingService;
use Modules\Tournaments\Models\TournamentCategory;

class RoundRobinGenerator implements DrawGeneratorInterface
{
    public function __construct(private readonly SeedingService $seeding) {}

    public function generate(TournamentCategory $event, Collection $participants, array $settings = []): array
    {
        $groupSize = min(8, max(3, (int) ($settings['group_size'] ?? $event->group_size ?? 4)));
        $matches = [];
        $warnings = [];

        foreach ($this->seeding->ordered($participants)->chunk($groupSize)->values() as $groupIndex => $groupParticipants) {
            $groupParticipants = $groupParticipants->values();
            $groupName = 'Group '.chr(65 + $groupIndex);
            $position = 0;

            for ($i = 0; $i < $groupParticipants->count(); $i++) {
                for ($j = $i + 1; $j < $groupParticipants->count(); $j++) {
                    $sideA = $groupParticipants->get($i);
                    $sideB = $groupParticipants->get($j);
                    $matches[] = [
                        'draw_type' => 'round_robin',
                        'stage' => StageType::Pool->value,
                        'round' => 1,
                        'round_label' => $groupName,
                        'position' => ++$position,
                        'group' => $groupName,
                        'side_a' => $sideA,
                        'side_b' => $sideB,
                        'participant_ids' => [$sideA->id, $sideB->id],
                        'is_bye' => false,
                        'feed_rule' => null,
                    ];
                }
            }

            if ($groupParticipants->count() < 3) {
                $warnings[] = "{$groupName} has fewer than three participants.";
            }
        }

        return [
            'event' => $event,
            'draw_type' => 'round_robin',
            'draw_size' => $participants->count(),
            'matches' => $matches,
            'warnings' => $warnings,
        ];
    }
}
