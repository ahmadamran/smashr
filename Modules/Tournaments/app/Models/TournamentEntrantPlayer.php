<?php

namespace Modules\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tournament_entrant_id', 'user_id', 'display_name', 'position'])]
class TournamentEntrantPlayer extends Model
{
    public function entrant(): BelongsTo
    {
        return $this->belongsTo(TournamentEntrant::class, 'tournament_entrant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->user?->playerProfile?->display_name
            ?? $this->user?->name
            ?? $this->display_name
            ?? 'Unassigned player';
    }
}
