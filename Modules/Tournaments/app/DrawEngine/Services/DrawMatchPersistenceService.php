<?php

namespace Modules\Tournaments\DrawEngine\Services;

use Illuminate\Validation\ValidationException;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;
use Modules\Tournaments\Models\TournamentCategory;

class DrawMatchPersistenceService
{
    public function __construct(private readonly MatchScoreService $scores) {}

    public function persist(TournamentCategory $event, array $draw, bool $overwrite = false): int
    {
        $existing = MatchRecord::where('tournament_category_id', $event->id)->exists();

        if ($existing && ! $overwrite) {
            throw ValidationException::withMessages([
                'confirm_overwrite' => 'Matches already exist for this event. Confirm safe overwrite to regenerate.',
            ]);
        }

        if ($overwrite) {
            MatchRecord::where('tournament_category_id', $event->id)->delete();
        }

        $created = 0;

        foreach ($draw['matches'] as $match) {
            if (empty($match['side_a']) || empty($match['side_b'])) {
                continue;
            }

            $record = MatchRecord::create([
                'format' => $event->format === 'singles' ? 'singles' : 'doubles',
                'submitted_by' => $event->tournament->organizer_id ?? $match['side_a']->created_by,
                'club_id' => $event->tournament->club_id,
                'tournament_id' => $event->tournament_id,
                'tournament_category_id' => $event->id,
                'status' => 'pending_confirmation',
                'played_at' => $match['scheduled_at']?->toDateString() ?? $event->tournament->starts_at?->toDateString() ?? now()->toDateString(),
                'scheduled_at' => $match['scheduled_at'],
                'court_label' => $match['court_label'],
                'estimated_duration_minutes' => $match['estimated_duration_minutes'] ?? null,
                'score' => [],
                'winner_side' => 'A',
                'draw_round' => $match['round'],
                'draw_group' => $match['group'] ?: $match['stage'],
                'draw_position' => $match['position'],
                'live_status' => 'scheduled',
                'live_score' => $this->scores->initialLiveScore(),
            ]);

            $this->scores->ensureScoreSheetToken($record);
            $this->attachEntrant($record, $match['side_a'], 'A');
            $this->attachEntrant($record, $match['side_b'], 'B');
            $created++;
        }

        return $created;
    }

    private function attachEntrant(MatchRecord $match, $entrant, string $side): void
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
