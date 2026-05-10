<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserAdminService
{
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->playerProfile()->create([
            'display_name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.$user->id,
            'smashr_points' => (int) ($data['smashr_points'] ?? 0),
        ]);

        $this->syncClub($user, $data['club_id'] ?? null);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = ['name' => $data['name'], 'email' => $data['email']];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->playerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $data['name'], 'slug' => $user->playerProfile?->slug ?? Str::slug($data['name']).'-'.$user->id],
        );
        $this->syncClub($user, $data['club_id'] ?? null);

        return $user;
    }

    public function setSuperadmin(User $user, bool $enabled): void
    {
        $role = Role::findOrCreate('superadmin', 'web');
        $enabled ? $user->assignRole($role) : $user->removeRole($role);
    }

    public function setSuspended(User $user, bool $suspended): void
    {
        $user->forceFill(['suspended_at' => $suspended ? now() : null])->save();
    }

    private function syncClub(User $user, mixed $clubId): void
    {
        $user->clubs()->sync($clubId ? [$clubId] : []);
    }
}
