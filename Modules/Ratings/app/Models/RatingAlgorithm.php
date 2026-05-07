<?php

namespace Modules\Ratings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['created_by', 'name', 'version', 'status', 'settings', 'activated_at'])]
class RatingAlgorithm extends Model
{
    public const DEFAULT_SETTINGS = [
        'starting_rating' => 3.500,
        'min_rating' => 1.000,
        'max_rating' => 8.000,
        'base_delta' => 0.180,
        'margin_weight' => 0.006,
        'max_margin_bonus' => 15,
        'rating_scale_divisor' => 2.000,
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'activated_at' => 'datetime',
        ];
    }

    public static function active(): self
    {
        return static::where('status', 'active')->first()
            ?? static::create([
                'name' => 'Smashr Rating v1',
                'version' => 'v1',
                'status' => 'active',
                'settings' => static::DEFAULT_SETTINGS,
                'activated_at' => now(),
            ]);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
