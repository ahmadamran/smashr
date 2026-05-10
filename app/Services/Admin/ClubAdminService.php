<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Clubs\Models\Club;

class ClubAdminService
{
    public function create(array $data): Club
    {
        return Club::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);
    }

    public function update(Club $club, array $data): Club
    {
        $club->update($data);

        return $club;
    }

    public function addMember(Club $club, string $email): void
    {
        $club->members()->syncWithoutDetaching([User::where('email', $email)->value('id')]);
    }
}
