<?php

namespace Modules\Matches\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Matches\Models\MatchRecord;

class MixedDoublesTeamValidator
{
    public function validateMatch(MatchRecord $match): void
    {
        if ($match->format !== 'mixed') {
            return;
        }

        $match->loadMissing('players.user.playerProfile');
        $players = $match->players->groupBy('side');

        $this->validateSide($players->get('A', collect())->pluck('user'), 'Side A');
        $this->validateSide($players->get('B', collect())->pluck('user'), 'Side B');
    }

    public function validateUserIds(string $format, array $sideAUserIds, array $sideBUserIds): void
    {
        if ($format !== 'mixed') {
            return;
        }

        $ids = collect([...$sideAUserIds, ...$sideBUserIds])->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $users = User::with('playerProfile')->whereIn('id', $ids)->get()->keyBy('id');

        $this->validateSide(collect($sideAUserIds)->map(fn ($id) => $users->get((int) $id)), 'Side A');
        $this->validateSide(collect($sideBUserIds)->map(fn ($id) => $users->get((int) $id)), 'Side B');
    }

    private function validateSide(Collection $users, string $label): void
    {
        $users = $users->filter()->values();

        if ($users->count() !== 2) {
            throw ValidationException::withMessages([
                'match' => "{$label} must have exactly two players for mixed doubles.",
            ]);
        }

        $genders = $users
            ->map(fn (User $user) => $user->playerProfile?->gender)
            ->sort()
            ->values()
            ->all();

        if ($genders !== ['female', 'male']) {
            throw ValidationException::withMessages([
                'match' => 'Mixed doubles requires one male and one female player on each side. Make sure player profiles have gender set.',
            ]);
        }
    }
}
