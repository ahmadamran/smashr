<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'phone_number' => $data['phone_number'] ?? null,
            'country' => $data['country'] ?? 'Malaysia',
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
            [
                'display_name' => $data['name'],
                'slug' => $user->playerProfile?->slug ?? Str::slug($data['name']).'-'.$user->id,
                'phone_number' => $data['phone_number'] ?? null,
                'country' => $data['country'] ?? 'Malaysia',
            ],
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

    public function merge(User $target, string $sourceIds): int
    {
        $ids = $this->sourceIds($sourceIds, $target->id);

        if ($ids === []) {
            throw ValidationException::withMessages(['source_ids' => 'Enter at least one different user ID to merge.']);
        }

        return DB::transaction(function () use ($target, $ids) {
            $target->loadMissing('playerProfile', 'roles');
            $sources = User::with('playerProfile', 'roles')->whereIn('id', $ids)->whereKeyNot($target->id)->get();

            if ($sources->isEmpty()) {
                throw ValidationException::withMessages(['source_ids' => 'No matching duplicate users were found.']);
            }

            foreach ($sources as $source) {
                $this->mergeProfile($target, $source);
                $this->mergeRoles($target, $source);

                $clubIds = DB::table('club_player')->where('user_id', $source->id)->pluck('club_id')->all();
                $target->clubs()->syncWithoutDetaching($clubIds);
                DB::table('club_player')->where('user_id', $source->id)->delete();

                DB::table('matches')->where('submitted_by', $source->id)->update(['submitted_by' => $target->id]);
                DB::table('tournaments')->where('organizer_id', $source->id)->update(['organizer_id' => $target->id]);
                DB::table('tournament_entrants')->where('created_by', $source->id)->update(['created_by' => $target->id]);
                DB::table('tournament_entrant_players')->where('user_id', $source->id)->update(['user_id' => $target->id]);
                DB::table('rating_algorithms')->where('created_by', $source->id)->update(['created_by' => $target->id]);
                DB::table('smashr_point_adjustments')->where('user_id', $source->id)->update(['user_id' => $target->id]);
                DB::table('smashr_point_adjustments')->where('admin_id', $source->id)->update(['admin_id' => $target->id]);

                $duplicateMatchPlayerIds = DB::table('match_players as source_players')
                    ->where('source_players.user_id', $source->id)
                    ->whereExists(function ($query) use ($target) {
                        $query->selectRaw('1')
                            ->from('match_players as target_players')
                            ->whereColumn('target_players.match_id', 'source_players.match_id')
                            ->where('target_players.user_id', $target->id);
                    })
                    ->pluck('source_players.id');

                DB::table('match_players')->whereIn('id', $duplicateMatchPlayerIds)->delete();

                $duplicateRatingEventIds = DB::table('rating_events as source_events')
                    ->where('source_events.user_id', $source->id)
                    ->whereExists(function ($query) use ($target) {
                        $query->selectRaw('1')
                            ->from('rating_events as target_events')
                            ->whereColumn('target_events.match_id', 'source_events.match_id')
                            ->where('target_events.user_id', $target->id);
                    })
                    ->pluck('source_events.id');

                DB::table('rating_events')->whereIn('id', $duplicateRatingEventIds)->delete();

                DB::table('match_players')->where('user_id', $source->id)->update(['user_id' => $target->id]);
                DB::table('rating_events')->where('user_id', $source->id)->update(['user_id' => $target->id]);

                DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $source->id)->delete();
                DB::table('model_has_permissions')->where('model_type', User::class)->where('model_id', $source->id)->delete();

                $source->delete();
            }

            return $sources->count();
        });
    }

    private function syncClub(User $user, mixed $clubId): void
    {
        if ($clubId) {
            $user->clubs()->syncWithoutDetaching([$clubId]);
        }
    }

    private function mergeProfile(User $target, User $source): void
    {
        $sourceProfile = $source->playerProfile;

        if (! $sourceProfile) {
            return;
        }

        $targetProfile = $target->playerProfile;

        if (! $targetProfile) {
            $sourceProfile->forceFill(['user_id' => $target->id])->save();
            $target->setRelation('playerProfile', $sourceProfile);

            return;
        }

        $fillFromSource = collect([
            'phone_number',
            'gender',
            'birthdate',
            'country',
            'state',
            'city',
            'postal_code',
            'preferred_hand',
            'primary_format',
        ])->filter(fn (string $field) => blank($targetProfile->{$field}) && filled($sourceProfile->{$field}))
            ->mapWithKeys(fn (string $field) => [$field => $sourceProfile->{$field}])
            ->all();

        $targetProfile->forceFill([
            ...$fillFromSource,
            'smashr_points' => (int) $targetProfile->smashr_points + (int) $sourceProfile->smashr_points,
        ])->save();
    }

    private function mergeRoles(User $target, User $source): void
    {
        foreach ($source->roles as $role) {
            if (! $target->hasRole($role)) {
                $target->assignRole($role);
            }
        }
    }

    private function sourceIds(string $sourceIds, int $targetId): array
    {
        return collect(preg_split('/[\s,]+/', $sourceIds, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== $targetId)
            ->unique()
            ->values()
            ->all();
    }
}
