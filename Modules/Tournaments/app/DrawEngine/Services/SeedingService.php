<?php

namespace Modules\Tournaments\DrawEngine\Services;

use Illuminate\Support\Collection;
use Modules\Tournaments\Models\TournamentEntrant;

class SeedingService
{
    public function ordered(Collection $participants): Collection
    {
        return $participants
            ->sortBy(fn (TournamentEntrant $entrant) => $entrant->seed ?? 9999)
            ->values();
    }

    public function nextPowerOfTwo(int $count): int
    {
        $drawSize = 2;

        while ($drawSize < max(2, $count)) {
            $drawSize *= 2;
        }

        return $drawSize;
    }

    public function singleEliminationSlots(Collection $participants): Collection
    {
        $ordered = $this->ordered($participants);
        $drawSize = $this->nextPowerOfTwo($ordered->count());
        $byeCount = $drawSize - $ordered->count();
        $slots = collect(array_fill(0, $drawSize, null));

        foreach ($ordered->take($byeCount)->values() as $index => $entrant) {
            $slots[($index * 2)] = $entrant;
        }

        $playingEntrants = $ordered->slice($byeCount)->values();
        $playingOffset = $drawSize - $playingEntrants->count();

        foreach ($playingEntrants as $index => $entrant) {
            $slots[$playingOffset + $index] = $entrant;
        }

        return $slots;
    }
}
