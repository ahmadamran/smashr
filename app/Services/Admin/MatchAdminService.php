<?php

namespace App\Services\Admin;

use Illuminate\Validation\ValidationException;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MixedDoublesTeamValidator;
use Modules\Ratings\Services\RatingService;

class MatchAdminService
{
    public function create(array $data, int $adminId): MatchRecord
    {
        $sideA = collect([$data['side_a_user_id'], $data['side_a_2_user_id'] ?? null])->filter()->map(fn ($id) => (int) $id)->values();
        $sideB = collect([$data['side_b_user_id'], $data['side_b_2_user_id'] ?? null])->filter()->map(fn ($id) => (int) $id)->values();
        $teamFormat = in_array($data['format'], ['doubles', 'mixed'], true);

        if ($teamFormat && ($sideA->count() !== 2 || $sideB->count() !== 2)) {
            throw ValidationException::withMessages(['match' => 'Doubles and mixed matches need two players on each side.']);
        }

        if ($sideA->merge($sideB)->unique()->count() !== $sideA->count() + $sideB->count()) {
            throw ValidationException::withMessages(['match' => 'Each player can only appear once in a match.']);
        }

        app(MixedDoublesTeamValidator::class)->validateUserIds($data['format'], $sideA->all(), $sideB->all());

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

        foreach ($sideA as $position => $userId) {
            $match->players()->create(['user_id' => $userId, 'side' => 'A', 'position' => $position + 1]);
        }

        foreach ($sideB as $position => $userId) {
            $match->players()->create(['user_id' => $userId, 'side' => 'B', 'position' => $position + 1]);
        }

        return $match;
    }

    public function update(MatchRecord $match, array $data, RatingService $ratings): MatchRecord
    {
        if (($data['format'] ?? $match->format) === 'mixed') {
            $match->loadMissing('players');
            $players = $match->players->groupBy('side');
            app(MixedDoublesTeamValidator::class)->validateUserIds(
                'mixed',
                $players->get('A', collect())->pluck('user_id')->all(),
                $players->get('B', collect())->pluck('user_id')->all(),
            );
        }

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
