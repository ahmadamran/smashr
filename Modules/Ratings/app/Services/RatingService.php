<?php

namespace Modules\Ratings\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Matches\Models\MatchPlayer;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MixedDoublesTeamValidator;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Models\RatingEvent;

class RatingService
{
    public function confirmForUser(MatchRecord $match, int $userId): MatchRecord
    {
        return DB::transaction(function () use ($match, $userId) {
            $player = $match->players()->where('user_id', $userId)->firstOrFail();
            $player->forceFill(['confirmed_at' => now(), 'disputed_at' => null])->save();

            $match->refresh()->load('players.user.playerProfile');

            if ($match->status === 'pending_confirmation' && $match->players->every(fn (MatchPlayer $player) => $player->confirmed_at !== null)) {
                $this->applyRatings($match);
                $match->forceFill(['status' => 'confirmed'])->save();
            }

            return $match->refresh();
        });
    }

    public function confirmAsAdmin(MatchRecord $match, ?RatingAlgorithm $algorithm = null): MatchRecord
    {
        return DB::transaction(function () use ($match, $algorithm) {
            $match->load('players.user.playerProfile');

            foreach ($match->players as $player) {
                $player->forceFill(['confirmed_at' => $player->confirmed_at ?? now(), 'disputed_at' => null])->save();
            }

            if ($match->status !== 'confirmed') {
                $this->applyRatings($match->refresh()->load('players.user.playerProfile'), $algorithm);
                $match->forceFill(['status' => 'confirmed'])->save();
            }

            return $match->refresh();
        });
    }

    public function disputeForUser(MatchRecord $match, int $userId): MatchRecord
    {
        $player = $match->players()->where('user_id', $userId)->firstOrFail();
        $player->forceFill(['disputed_at' => now()])->save();
        $match->forceFill(['status' => 'disputed'])->save();

        return $match->refresh();
    }

    private function applyRatings(MatchRecord $match, ?RatingAlgorithm $algorithm = null): void
    {
        if ($match->status === 'confirmed' || RatingEvent::where('match_id', $match->id)->exists()) {
            return;
        }

        app(MixedDoublesTeamValidator::class)->validateMatch($match);

        $algorithm ??= RatingAlgorithm::active();
        $settings = array_replace(RatingAlgorithm::DEFAULT_SETTINGS, $algorithm->settings ?? []);
        [$ratingColumn, $countColumn] = match ($match->format) {
            'mixed' => ['mixed_rating', 'mixed_matches'],
            'doubles' => ['doubles_rating', 'doubles_matches'],
            default => ['singles_rating', 'singles_matches'],
        };
        $players = $match->players->groupBy('side');
        $sideA = $players->get('A', collect());
        $sideB = $players->get('B', collect());

        $ratingA = $this->teamRating($sideA, $ratingColumn, (float) $settings['starting_rating']);
        $ratingB = $this->teamRating($sideB, $ratingColumn, (float) $settings['starting_rating']);
        $margin = $this->scoreMargin($match->score ?? []);

        $expectedA = 1 / (1 + 10 ** (($ratingB - $ratingA) / (float) $settings['rating_scale_divisor']));
        $winnerA = $match->winner_side === 'A' ? 1 : 0;
        $baseDelta = ((float) $settings['base_delta'] + min($margin, (int) $settings['max_margin_bonus']) * (float) $settings['margin_weight']) * ($winnerA - $expectedA);

        $this->applySide($match, $sideA, $ratingColumn, $countColumn, $baseDelta, $settings, $algorithm);
        $this->applySide($match, $sideB, $ratingColumn, $countColumn, -$baseDelta, $settings, $algorithm);
    }

    private function teamRating(Collection $players, string $ratingColumn, float $default): float
    {
        return (float) $players->avg(fn (MatchPlayer $player) => $player->user->playerProfile->{$ratingColumn} ?? $default);
    }

    private function scoreMargin(array $score): int
    {
        return collect($score)->sum(fn (array $game) => abs((int) ($game['a'] ?? 0) - (int) ($game['b'] ?? 0)));
    }

    private function applySide(MatchRecord $match, Collection $side, string $ratingColumn, string $countColumn, float $delta, array $settings, RatingAlgorithm $algorithm): void
    {
        foreach ($side as $matchPlayer) {
            $profile = $matchPlayer->user->playerProfile;
            $before = (float) $profile->{$ratingColumn};
            $after = round(max((float) $settings['min_rating'], min((float) $settings['max_rating'], $before + $delta)), 3);

            $profile->forceFill([
                $ratingColumn => $after,
                $countColumn => $profile->{$countColumn} + 1,
            ])->save();

            $matchPlayer->forceFill([
                'rating_before' => $before,
                'rating_after' => $after,
            ])->save();

            RatingEvent::create([
                'match_id' => $match->id,
                'rating_algorithm_id' => $algorithm->id,
                'user_id' => $matchPlayer->user_id,
                'format' => $match->format,
                'rating_before' => $before,
                'rating_after' => $after,
                'delta' => round($after - $before, 3),
                'reason' => 'confirmed_match',
            ]);
        }
    }
}
