<?php

namespace Modules\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tournament_id',
    'tournament_category_id',
    'created_by',
    'name',
    'contact_name',
    'contact_phone',
    'identity_type',
    'identity_number',
    'identity_document_path',
    'kyc_status',
    'status',
    'seed',
    'draw_position',
    'group_name',
])]
class TournamentEntrant extends Model
{
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentEntrantPlayer::class);
    }

    public function displayName(): string
    {
        return $this->name ?: $this->players->map(fn (TournamentEntrantPlayer $player) => $player->displayName())->filter()->join(' / ');
    }
}
