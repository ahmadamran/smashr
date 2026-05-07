<?php

namespace Modules\Tournaments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;

#[Fillable(['club_id', 'organizer_id', 'name', 'slug', 'country', 'state', 'city', 'venue', 'starts_at', 'ends_at', 'status', 'registration_mode', 'registration_status', 'registration_deadline'])]
class Tournament extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'registration_deadline' => 'date',
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

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchRecord::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TournamentCategory::class);
    }

    public function entrants(): HasMany
    {
        return $this->hasMany(TournamentEntrant::class);
    }

    public function registrationOpen(): bool
    {
        return $this->registration_status === 'open'
            && $this->registration_mode === 'public'
            && ($this->registration_deadline === null || $this->registration_deadline->endOfDay()->isFuture());
    }
}
