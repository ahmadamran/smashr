<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ratings\Models\RatingAlgorithm;

class AlgorithmAdminService
{
    public function create(array $data, int $adminId): RatingAlgorithm
    {
        return RatingAlgorithm::create([
            'created_by' => $adminId,
            'name' => $data['name'],
            'version' => $data['version'],
            'status' => 'draft',
            'settings' => $data['settings'],
        ]);
    }

    public function update(RatingAlgorithm $algorithm, array $data): RatingAlgorithm
    {
        abort_if($algorithm->status === 'active', 422, 'Active algorithms cannot be edited. Create a new draft version instead.');

        $algorithm->update([
            'name' => $data['name'],
            'version' => $data['version'],
            'settings' => $data['settings'],
        ]);

        return $algorithm;
    }

    public function duplicate(RatingAlgorithm $algorithm, int $adminId): RatingAlgorithm
    {
        $copy = $algorithm->replicate();
        $copy->forceFill([
            'status' => 'draft',
            'activated_at' => null,
            'version' => $algorithm->version.'-copy-'.Str::lower(Str::random(3)),
            'created_by' => $adminId,
        ])->save();

        return $copy;
    }

    public function activate(RatingAlgorithm $algorithm): void
    {
        DB::transaction(function () use ($algorithm) {
            RatingAlgorithm::where('status', 'active')->whereKeyNot($algorithm->id)->update(['status' => 'archived']);
            $algorithm->forceFill(['status' => 'active', 'activated_at' => now()])->save();
        });
    }

    public function archive(RatingAlgorithm $algorithm): void
    {
        abort_if($algorithm->status === 'active', 422, 'Activate another algorithm before archiving this one.');
        $algorithm->forceFill(['status' => 'archived'])->save();
    }

    public function deleteDraft(RatingAlgorithm $algorithm): void
    {
        abort_if($algorithm->status === 'active', 422, 'Activate another algorithm before deleting this one.');
        abort_if($algorithm->status !== 'draft', 422, 'Only draft algorithms can be deleted.');
        $algorithm->delete();
    }
}
