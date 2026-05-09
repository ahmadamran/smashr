<?php

namespace Modules\Tournaments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Matches\Models\MatchRecord;

#[Fillable(['tournament_id', 'name', 'slug', 'format', 'level_label', 'draw_mode', 'group_size', 'max_entrants', 'status'])]
class TournamentCategory extends Model
{
    protected function casts(): array
    {
        return [
            'group_size' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function entrants(): HasMany
    {
        return $this->hasMany(TournamentEntrant::class);
    }

    public function approvedEntrants(): HasMany
    {
        return $this->entrants()->where('status', 'approved');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class);
    }
}
