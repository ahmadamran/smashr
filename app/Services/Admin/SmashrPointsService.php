<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Players\Models\SmashrPointAdjustment;

class SmashrPointsService
{
    public function adjust(User $user, string $mode, int $points, string $reason, ?int $adminId = null): SmashrPointAdjustment
    {
        $profile = $this->profile($user);
        $before = (int) $profile->smashr_points;
        $after = match ($mode) {
            'set' => $points,
            'add' => $before + $points,
            'deduct' => max(0, $before - $points),
        };

        $profile->forceFill(['smashr_points' => $after])->save();

        return SmashrPointAdjustment::create([
            'user_id' => $user->id,
            'admin_id' => $adminId,
            'before_points' => $before,
            'adjustment' => $after - $before,
            'after_points' => $after,
            'reason' => $reason,
        ]);
    }

    public function regenerate(User $user, ?int $adminId = null): SmashrPointAdjustment
    {
        $points = $user->matchPlayers()->whereHas('match', fn ($query) => $query->where('status', 'confirmed'))->count() * 10;

        return $this->adjust($user, 'set', $points, 'Admin regenerated SMASHR points from confirmed matches.', $adminId);
    }

    private function profile(User $user)
    {
        return $user->playerProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'slug' => Str::slug($user->name).'-'.$user->id],
        );
    }
}
