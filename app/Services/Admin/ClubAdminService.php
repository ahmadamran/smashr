<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    public function merge(Club $target, string $sourceIds): int
    {
        $ids = $this->sourceIds($sourceIds, $target->id);

        if ($ids === []) {
            throw ValidationException::withMessages(['source_ids' => 'Enter at least one different club ID to merge.']);
        }

        return DB::transaction(function () use ($target, $ids) {
            $sources = Club::whereIn('id', $ids)->whereKeyNot($target->id)->get();

            if ($sources->isEmpty()) {
                throw ValidationException::withMessages(['source_ids' => 'No matching duplicate clubs were found.']);
            }

            foreach ($sources as $source) {
                $memberIds = DB::table('club_player')
                    ->where('club_id', $source->id)
                    ->pluck('user_id')
                    ->all();

                $target->members()->syncWithoutDetaching($memberIds);

                DB::table('matches')->where('club_id', $source->id)->update(['club_id' => $target->id]);
                DB::table('tournaments')->where('club_id', $source->id)->update(['club_id' => $target->id]);
                DB::table('club_player')->where('club_id', $source->id)->delete();

                $source->delete();
            }

            return $sources->count();
        });
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
