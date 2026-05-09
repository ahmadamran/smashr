<?php

namespace Modules\Tournaments\DrawEngine\Generators;

use Illuminate\Support\Collection;
use Modules\Tournaments\DrawEngine\Contracts\DrawGeneratorInterface;
use Modules\Tournaments\DrawEngine\Enums\StageType;
use Modules\Tournaments\DrawEngine\Services\FeedRuleService;
use Modules\Tournaments\DrawEngine\Services\SeedingService;
use Modules\Tournaments\Models\TournamentCategory;

class PoolKnockoutGenerator implements DrawGeneratorInterface
{
    public function __construct(private readonly SeedingService $seeding, private readonly FeedRuleService $feeds) {}

    public function generate(TournamentCategory $event, Collection $participants, array $settings = []): array
    {
        $groupSize = min(8, max(3, (int) ($settings['group_size'] ?? $event->group_size ?? 4)));
        $qualifiersPerPool = min(4, max(1, (int) ($settings['qualifiers_per_pool'] ?? 2)));
        $matches = [];
        $warnings = [];
        $groups = $this->seeding->ordered($participants)->chunk($groupSize)->values();

        foreach ($groups as $groupIndex => $groupParticipants) {
            $groupParticipants = $groupParticipants->values();
            $groupName = 'Group '.chr(65 + $groupIndex);
            $position = 0;

            for ($i = 0; $i < $groupParticipants->count(); $i++) {
                for ($j = $i + 1; $j < $groupParticipants->count(); $j++) {
                    $sideA = $groupParticipants->get($i);
                    $sideB = $groupParticipants->get($j);
                    $matches[] = [
                        'draw_type' => 'pool_to_knockout',
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
        }

        $qualifierSlots = $groups->count() * $qualifiersPerPool;
        $knockoutSize = $this->seeding->nextPowerOfTwo($qualifierSlots);
        for ($position = 1; $position <= intdiv($knockoutSize, 2); $position++) {
            $poolA = chr(65 + (($position - 1) % max(1, $groups->count())));
            $poolB = chr(65 + (($position) % max(1, $groups->count())));
            $matches[] = [
                'draw_type' => 'pool_to_knockout',
                'stage' => StageType::Knockout->value,
                'round' => 1,
                'round_label' => 'Knockout round 1',
                'position' => $position,
                'group' => 'Knockout',
                'side_a' => null,
                'side_b' => null,
                'participant_ids' => [],
                'is_bye' => false,
                'feed_rule' => $this->feeds->poolWinnerRule($poolA).' vs '.$this->feeds->poolWinnerRule($poolB),
            ];
        }

        if ($groups->count() < 2) {
            $warnings[] = 'Pool to knockout works best with at least two pools.';
        }

        return [
            'event' => $event,
            'draw_type' => 'pool_to_knockout',
            'draw_size' => $participants->count(),
            'matches' => $matches,
            'warnings' => $warnings,
        ];
    }
}
