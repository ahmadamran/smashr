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
    'country',
    'state',
    'city',
    'preferred_hand',
    'primary_format',
    'singles_rating',
    'doubles_rating',
    'singles_matches',
    'doubles_matches',
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
            'singles_rating' => 'decimal:3',
            'doubles_rating' => 'decimal:3',
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
