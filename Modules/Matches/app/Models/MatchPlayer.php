<?php

namespace Modules\Matches\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'match_id',
    'user_id',
    'side',
    'position',
    'rating_before',
    'rating_after',
    'confirmed_at',
    'disputed_at',
])]
class MatchPlayer extends Model
{
    protected function casts(): array
    {
        return [
            'rating_before' => 'decimal:3',
            'rating_after' => 'decimal:3',
            'confirmed_at' => 'datetime',
            'disputed_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchRecord::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
