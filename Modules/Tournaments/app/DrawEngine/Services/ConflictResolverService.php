<?php

namespace Modules\Tournaments\DrawEngine\Services;

use Carbon\CarbonInterface;

class ConflictResolverService
{
    public function canPlace(array $match, CarbonInterface $startsAt, int $durationMinutes, int $restMinutes, array $playerSchedule, int $maxMatchesPerDay): bool
    {
        $day = $startsAt->toDateString();
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

        foreach ($match['participant_ids'] ?? [] as $participantId) {
            $playerDay = $playerSchedule[$participantId][$day] ?? [];

            if (count($playerDay) >= $maxMatchesPerDay) {
                return false;
            }

            foreach ($playerDay as $slot) {
                $blockedStart = $slot['start']->copy()->subMinutes($restMinutes);
                $blockedEnd = $slot['end']->copy()->addMinutes($restMinutes);

                if ($startsAt->lt($blockedEnd) && $endsAt->gt($blockedStart)) {
                    return false;
                }
            }
        }

        return true;
    }
}
