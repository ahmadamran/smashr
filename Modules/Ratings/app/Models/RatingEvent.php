<?php

namespace Modules\Ratings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Matches\Models\MatchRecord;

#[Fillable(['match_id', 'rating_algorithm_id', 'user_id', 'format', 'rating_before', 'rating_after', 'delta', 'reason'])]
class RatingEvent extends Model
{
    protected function casts(): array
    {
        return [
            'rating_before' => 'decimal:3',
            'rating_after' => 'decimal:3',
            'delta' => 'decimal:3',
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

    public function algorithm(): BelongsTo
    {
        return $this->belongsTo(RatingAlgorithm::class, 'rating_algorithm_id');
    }
}
