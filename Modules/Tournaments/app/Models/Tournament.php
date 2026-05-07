<?php

namespace Modules\Tournaments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;

#[Fillable(['club_id', 'name', 'slug', 'country', 'state', 'city', 'starts_at', 'ends_at', 'status'])]
class Tournament extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class);
    }
}
