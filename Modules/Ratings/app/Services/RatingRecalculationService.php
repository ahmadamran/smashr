<?php

namespace Modules\Ratings\Services;

use Illuminate\Support\Facades\DB;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Models\RatingEvent;

class RatingRecalculationService
{
    public function preview(RatingAlgorithm $algorithm): array
    {
        $confirmedMatches = MatchRecord::where('status', 'confirmed')->count();
        $players = PlayerProfile::count();

        return [
            'algorithm' => $algorithm->version,
            'matches' => $confirmedMatches,
            'players' => $players,
            'message' => "Ready to recompute {$confirmedMatches} confirmed matches for {$players} players.",
        ];
    }

    public function apply(RatingAlgorithm $algorithm): array
    {
        return DB::transaction(function () use ($algorithm) {
            $settings = array_replace(RatingAlgorithm::DEFAULT_SETTINGS, $algorithm->settings ?? []);
            PlayerProfile::query()->update([
                'singles_rating' => $settings['starting_rating'],
                'doubles_rating' => $settings['starting_rating'],
                'mixed_rating' => $settings['starting_rating'],
                'singles_matches' => 0,
                'doubles_matches' => 0,
                'mixed_matches' => 0,
            ]);
            RatingEvent::query()->delete();

            foreach (MatchRecord::with('players.user.playerProfile')->where('status', 'confirmed')->oldest('played_at')->get() as $match) {
                $match->forceFill(['status' => 'pending_confirmation'])->save();
                app(RatingService::class)->confirmAsAdmin($match, $algorithm);
            }

            return $this->preview($algorithm);
        });
    }
}
