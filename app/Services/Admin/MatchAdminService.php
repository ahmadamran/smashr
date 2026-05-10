<?php

namespace App\Services\Admin;

use Modules\Matches\Models\MatchRecord;
use Modules\Ratings\Services\RatingService;

class MatchAdminService
{
    public function create(array $data, int $adminId): MatchRecord
    {
        $match = MatchRecord::create([
            'format' => $data['format'],
            'submitted_by' => $adminId,
            'club_id' => $data['club_id'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category_id' => $data['tournament_category_id'] ?? null,
            'status' => $data['status'] ?? 'pending_confirmation',
            'played_at' => $data['played_at'] ?? now()->toDateString(),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'court_label' => $data['court_label'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'score' => [],
            'winner_side' => $data['winner_side'] ?? 'A',
        ]);

        $match->players()->create(['user_id' => $data['side_a_user_id'], 'side' => 'A', 'position' => 1]);
        $match->players()->create(['user_id' => $data['side_b_user_id'], 'side' => 'B', 'position' => 1]);

        return $match;
    }

    public function update(MatchRecord $match, array $data, RatingService $ratings): MatchRecord
    {
        $match->update($data);

        if ($data['status'] === 'confirmed') {
            $ratings->confirmAsAdmin($match->refresh());
        }

        return $match;
    }

    public function markDisputed(MatchRecord $match): void
    {
        $match->update(['status' => 'disputed']);
    }

    public function void(MatchRecord $match): void
    {
        $match->update(['status' => 'void']);
    }

    public function bulk(array $matchIds, string $action, RatingService $ratings): array
    {
        $matches = MatchRecord::whereIn('id', $matchIds)->get();
        $updated = 0;
        $failed = 0;

        foreach ($matches as $match) {
            try {
                if ($action === 'confirm') {
                    $ratings->confirmAsAdmin($match);
                }

                if ($action === 'void') {
                    $this->void($match);
                }

                $updated++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['updated' => $updated, 'failed' => $failed];
    }
}
