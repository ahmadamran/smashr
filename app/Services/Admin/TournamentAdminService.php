<?php

namespace App\Services\Admin;

use Illuminate\Support\Str;
use Modules\Tournaments\Models\Tournament;

class TournamentAdminService
{
    public function create(array $data, int $organizerId): Tournament
    {
        return Tournament::create([
            ...$data,
            'organizer_id' => $organizerId,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'registration_mode' => 'public',
            'registration_status' => 'open',
        ]);
    }

    public function update(Tournament $tournament, array $data): Tournament
    {
        $tournament->update($data);

        return $tournament;
    }

    public function archive(Tournament $tournament): void
    {
        $tournament->update(['status' => 'archived']);
    }
}
