<?php

namespace Modules\Tournaments\DrawEngine\Services;

use Illuminate\Validation\ValidationException;
use Modules\Tournaments\DrawEngine\Enums\DrawType;
use Modules\Tournaments\DrawEngine\Generators\DoubleEliminationGenerator;
use Modules\Tournaments\DrawEngine\Generators\PoolKnockoutGenerator;
use Modules\Tournaments\DrawEngine\Generators\RoundRobinGenerator;
use Modules\Tournaments\DrawEngine\Generators\SingleEliminationGenerator;
use Modules\Tournaments\Models\TournamentCategory;

class DrawGeneratorService
{
    public function __construct(
        private readonly SingleEliminationGenerator $singleElimination,
        private readonly DoubleEliminationGenerator $doubleElimination,
        private readonly RoundRobinGenerator $roundRobin,
        private readonly PoolKnockoutGenerator $poolKnockout,
    ) {}

    public function preview(TournamentCategory $event, DrawType|string $drawType, array $settings = []): array
    {
        $event->loadMissing('approvedEntrants.players.user.playerProfile', 'tournament');
        $participants = $event->approvedEntrants
            ->sortBy(fn ($entrant) => $entrant->seed ?? 9999)
            ->values();

        if ($participants->count() < 2) {
            throw ValidationException::withMessages([
                'event_id' => 'At least two approved participants are required.',
            ]);
        }

        $drawType = $drawType instanceof DrawType ? $drawType : DrawType::from($drawType);

        return match ($drawType) {
            DrawType::SingleElimination => $this->singleElimination->generate($event, $participants, $settings),
            DrawType::DoubleElimination => $this->doubleElimination->generate($event, $participants, $settings),
            DrawType::RoundRobin => $this->roundRobin->generate($event, $participants, $settings),
            DrawType::PoolToKnockout => $this->poolKnockout->generate($event, $participants, $settings),
        };
    }
}
