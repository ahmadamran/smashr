<?php

namespace Modules\Tournaments\DrawEngine\Services;

use Illuminate\Support\Carbon;
use Modules\Tournaments\Models\Tournament;

class ScheduleGeneratorService
{
    public function __construct(private readonly ConflictResolverService $conflicts) {}

    public function schedule(Tournament $tournament, array $draw, array $settings = []): array
    {
        $settings = array_replace(config('draw-engine.defaults', []), $settings);
        $days = $this->normalizeDays($tournament, $settings);
        $duration = max(5, (int) ($settings['match_duration_minutes'] ?? 30));
        $rest = max(0, (int) ($settings['rest_minutes'] ?? 10));
        $maxPerPlayer = max(1, (int) ($settings['max_matches_per_player_per_day'] ?? 4));
        $courtPrefix = trim((string) ($settings['court_label_prefix'] ?? 'Court'));
        $warnings = $draw['warnings'] ?? [];
        $courtSchedule = [];
        $playerSchedule = [];
        $scheduledMatches = [];

        foreach ($draw['matches'] as $match) {
            if ($match['is_bye'] || empty($match['participant_ids'])) {
                $scheduledMatches[] = [...$match, 'scheduled_at' => null, 'court_label' => null, 'estimated_duration_minutes' => $duration, 'schedule_warning' => null];

                continue;
            }

            $placement = $this->placeMatch($match, $days, $courtSchedule, $playerSchedule, $duration, $rest, $maxPerPlayer, $courtPrefix);

            if (! $placement) {
                $warnings[] = "Could not schedule {$match['round_label']} match {$match['position']} inside the selected day/round limits.";
                $scheduledMatches[] = [...$match, 'scheduled_at' => null, 'court_label' => null, 'estimated_duration_minutes' => $duration, 'schedule_warning' => 'Impossible schedule'];

                continue;
            }

            [$startsAt, $courtLabel] = $placement;
            $endsAt = $startsAt->copy()->addMinutes($duration);
            $day = $startsAt->toDateString();
            $courtSchedule[$courtLabel][] = ['start' => $startsAt, 'end' => $endsAt];

            foreach ($match['participant_ids'] as $participantId) {
                $playerSchedule[$participantId][$day][] = ['start' => $startsAt, 'end' => $endsAt];
            }

            $scheduledMatches[] = [
                ...$match,
                'scheduled_at' => $startsAt,
                'court_label' => $courtLabel,
                'estimated_duration_minutes' => $duration,
                'schedule_warning' => null,
            ];
        }

        return [...$draw, 'matches' => $scheduledMatches, 'warnings' => array_values(array_unique($warnings))];
    }

    private function placeMatch(array $match, array $days, array $courtSchedule, array $playerSchedule, int $duration, int $rest, int $maxPerPlayer, string $courtPrefix): ?array
    {
        foreach ($days as $day) {
            if (! $this->dayAllows($day, $match)) {
                continue;
            }

            foreach (range(1, $day['courts_count']) as $courtNumber) {
                $courtLabel = trim($courtPrefix.' '.$courtNumber);
                $cursor = $day['start_at']->copy();
                $latestStart = $day['end_at']->copy()->subMinutes($duration);

                while ($cursor->lte($latestStart)) {
                    if ($this->courtFree($courtSchedule[$courtLabel] ?? [], $cursor, $duration)
                        && $this->conflicts->canPlace($match, $cursor, $duration, $rest, $playerSchedule, $maxPerPlayer)) {
                        return [$cursor->copy(), $courtLabel];
                    }

                    $cursor->addMinutes($duration + $rest);
                }
            }
        }

        return null;
    }

    private function courtFree(array $slots, Carbon $startsAt, int $duration): bool
    {
        $endsAt = $startsAt->copy()->addMinutes($duration);

        foreach ($slots as $slot) {
            if ($startsAt->lt($slot['end']) && $endsAt->gt($slot['start'])) {
                return false;
            }
        }

        return true;
    }

    private function dayAllows(array $day, array $match): bool
    {
        $stages = $day['allowed_stages'] ?? [];
        $rounds = $day['allowed_rounds'] ?? [];

        return (empty($stages) || in_array($match['stage'], $stages, true))
            && (empty($rounds) || in_array((string) $match['round'], $rounds, true));
    }

    private function normalizeDays(Tournament $tournament, array $settings): array
    {
        $rawDays = collect($settings['days'] ?? [])->filter(fn ($day) => filled($day['date'] ?? null))->values();

        if ($rawDays->isEmpty()) {
            $rawDays = collect([[
                'date' => $tournament->starts_at?->toDateString() ?? now()->toDateString(),
                'start_time' => $settings['schedule_start_time'] ?? '09:00',
                'end_time' => $settings['schedule_end_time'] ?? '18:00',
                'courts_count' => $settings['courts_count'] ?? 2,
                'allowed_stages' => [],
                'allowed_rounds' => [],
            ]]);
        }

        return $rawDays->map(fn ($day) => [
            'date' => $day['date'],
            'start_at' => Carbon::parse($day['date'].' '.($day['start_time'] ?? $settings['schedule_start_time'] ?? '09:00')),
            'end_at' => Carbon::parse($day['date'].' '.($day['end_time'] ?? $settings['schedule_end_time'] ?? '18:00')),
            'courts_count' => max(1, (int) ($day['courts_count'] ?? $settings['courts_count'] ?? 2)),
            'allowed_stages' => array_values(array_filter((array) ($day['allowed_stages'] ?? []))),
            'allowed_rounds' => array_map('strval', array_values(array_filter((array) ($day['allowed_rounds'] ?? [])))),
        ])->all();
    }
}
