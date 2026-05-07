<?php

namespace Modules\Matches\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Clubs\Models\Club;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\Tournament;

#[Fillable(['format', 'submitted_by', 'club_id', 'tournament_id', 'tournament_category_id', 'status', 'played_at', 'score', 'winner_side', 'draw_round', 'draw_group', 'draw_position', 'score_sheet_token', 'live_status', 'live_score', 'score_submitted_at'])]
class MatchRecord extends Model
{
    protected $table = 'matches';

    protected function casts(): array
    {
        return [
            'played_at' => 'date',
            'score' => 'array',
            'live_score' => 'array',
            'score_submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MatchRecord $match) {
            if (! $match->tournament_id || filled($match->score_sheet_token)) {
                return;
            }

            do {
                $token = Str::lower(Str::random(8));
            } while (self::where('score_sheet_token', $token)->exists());

            $match->score_sheet_token = $token;
            $score = is_array($match->score) ? $match->score : [];
            $match->live_status ??= empty($score) ? 'scheduled' : 'approved';
            $match->live_score ??= [
                'current_game' => count($score) + 1,
                'current' => ['a' => 0, 'b' => 0],
                'games' => $score,
                'history' => [],
            ];
        });
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
