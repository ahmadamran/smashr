<?php

namespace Modules\Matches\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Clubs\Models\Club;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\Tournament;

#[Fillable(['format', 'submitted_by', 'club_id', 'tournament_id', 'tournament_category_id', 'status', 'played_at', 'score', 'winner_side', 'draw_round', 'draw_group', 'draw_position'])]
class MatchRecord extends Model
{
    protected $table = 'matches';

    protected function casts(): array
    {
        return [
            'played_at' => 'date',
            'score' => 'array',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function tournamentCategory(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class);
    }
}
