<?php

namespace Modules\Players\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Clubs\Models\Club;

#[Fillable([
    'user_id',
    'display_name',
    'slug',
    'phone_number',
    'gender',
    'birthdate',
    'country',
    'state',
    'city',
    'postal_code',
    'preferred_hand',
    'primary_format',
    'smashr_points',
    'singles_rating',
    'doubles_rating',
    'mixed_rating',
    'singles_matches',
    'doubles_matches',
    'mixed_matches',
])]
class PlayerProfile extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'singles_rating' => 'decimal:3',
            'doubles_rating' => 'decimal:3',
            'mixed_rating' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): ?Club
    {
        return $this->user?->clubs()->first();
    }
}
