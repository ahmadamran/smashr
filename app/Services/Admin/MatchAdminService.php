<?php

namespace App\Services\Admin;

use Modules\Matches\Models\MatchRecord;
use Modules\Ratings\Services\RatingService;

class MatchAdminService
{
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
}
