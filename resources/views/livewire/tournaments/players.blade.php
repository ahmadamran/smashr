<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentEntrant;
use Modules\Tournaments\Models\TournamentEntrantPlayer;

new class extends Component
{
    public int $tournamentId;

    public string $search = '';

    public function mount(Tournament $tournament): void
    {
        $this->tournamentId = $tournament->id;
    }

    public function tournament(): Tournament
    {
        return Tournament::with('club', 'organizer', 'categories')->findOrFail($this->tournamentId);
    }

    public function approvedEntrants(): Collection
    {
        return TournamentEntrant::query()
            ->with('category', 'players.user.playerProfile')
            ->where('tournament_id', $this->tournamentId)
            ->where('status', 'approved')
            ->orderBy('tournament_category_id')
            ->orderByRaw('seed is null')
            ->orderBy('seed')
            ->orderBy('name')
            ->get();
    }

    public function filteredEntrants(): Collection
    {
        return $this->approvedEntrants()
            ->filter(fn (TournamentEntrant $entrant) => $this->entrantMatchesSearch($entrant))
            ->values();
    }

    public function directoryRows(): Collection
    {
        return $this->filteredEntrants()
            ->flatMap(function (TournamentEntrant $entrant) {
                return $entrant->players->map(fn (TournamentEntrantPlayer $player) => [
                    'key' => $this->playerKey($player),
                    'name' => $player->displayName(),
                    'school' => $player->school_name,
                    'profile' => $player->user?->playerProfile,
                    'singles_rating' => $player->user?->playerProfile?->singles_rating,
                    'singles_matches' => $player->user?->playerProfile?->singles_matches ?? 0,
                    'doubles_rating' => $player->user?->playerProfile?->doubles_rating,
                    'doubles_matches' => $player->user?->playerProfile?->doubles_matches ?? 0,
                    'category' => $entrant->category?->name ?? 'Unassigned event',
                    'seed' => $entrant->seed,
                ]);
            })
            ->groupBy('key')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'name' => $first['name'],
                    'school' => $first['school'],
                    'profile' => $first['profile'],
                    'singles_rating' => $first['singles_rating'],
                    'singles_matches' => $first['singles_matches'],
                    'doubles_rating' => $first['doubles_rating'],
                    'doubles_matches' => $first['doubles_matches'],
                    'categories' => $rows->pluck('category')->unique()->sort()->values(),
                    'seeds' => $rows->pluck('seed')->filter()->unique()->sort()->values(),
                ];
            })
            ->sortBy(fn (array $row) => Str::lower($row['name']))
            ->values();
    }

    public function entrantsByCategory(): Collection
    {
        return $this->filteredEntrants()
            ->groupBy(fn (TournamentEntrant $entrant) => $entrant->category?->id ?? 0)
            ->sortBy(fn (Collection $entrants) => $entrants->first()?->category?->name ?? 'Unassigned event');
    }

    private function entrantMatchesSearch(TournamentEntrant $entrant): bool
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return true;
        }

        return $entrant->players->contains(fn (TournamentEntrantPlayer $player) => str_contains($this->searchText($player), $search));
    }

    private function searchText(TournamentEntrantPlayer $player): string
    {
        return Str::lower(collect([
            $player->displayName(),
            $player->display_name,
            $player->user?->name,
            $player->user?->playerProfile?->display_name,
            $player->school_name,
        ])->filter()->join(' '));
    }

    private function playerKey(TournamentEntrantPlayer $player): string
    {
        if ($player->user_id) {
            return 'user:'.$player->user_id;
        }

        return 'guest:'.Str::lower($player->displayName()).'|'.Str::lower((string) $player->school_name);
    }

    private function normalizedSearch(): string
    {
        return Str::lower(trim($this->search));
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    @php
        $tournament = $this->tournament();
        $approvedEntrants = $this->approvedEntrants();
        $directoryRows = $this->directoryRows();
        $entrantsByCategory = $this->entrantsByCategory();
        $approvedPlayerCount = $approvedEntrants
            ->flatMap(fn ($entrant) => $entrant->players)
            ->unique(fn ($player) => $player->user_id ? 'user:'.$player->user_id : 'guest:'.Str::lower($player->displayName()).'|'.Str::lower((string) $player->school_name))
            ->count();
    @endphp

    @include('tournaments.partials.nav', ['tournament' => $tournament])
    @include('tournaments.partials.realtime-search', ['search' => $search, 'id' => 'tournament-player-search'])

    <section class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-lg">
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Players</p>
            <p class="mt-2 text-3xl font-black text-brand-blue">{{ $approvedPlayerCount }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-lg">
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Entrants</p>
            <p class="mt-2 text-3xl font-black text-brand-blue">{{ $approvedEntrants->count() }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-lg">
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Categories</p>
            <p class="mt-2 text-3xl font-black text-brand-blue">{{ $tournament->categories->count() }}</p>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-lg">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">All players</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">Player directory</h2>
            </div>
            @if ($search !== '')
                <p class="text-sm font-bold text-brand-ink/50">{{ $directoryRows->count() }} matches</p>
            @endif
        </div>

        <div class="mt-5 divide-y divide-brand-ink/10">
            @forelse ($directoryRows as $row)
                <article class="grid gap-3 py-4 lg:grid-cols-[1.1fr_1fr_.8fr] lg:items-center">
                    <div>
                        @if ($row['profile'])
                            <a href="{{ route('players.show', $row['profile']) }}" class="font-black text-brand-blue hover:text-brand-green">{{ $row['name'] }}</a>
                        @else
                            <p class="font-black text-brand-blue">{{ $row['name'] }}</p>
                        @endif
                        @if ($row['school'])
                            <p class="mt-1 text-sm font-bold text-brand-ink/55">{{ $row['school'] }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($row['categories'] as $categoryName)
                            <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ $categoryName }}</span>
                        @endforeach
                    </div>
                    <div class="text-sm font-bold text-brand-ink/60">
                        @if ($row['seeds']->isNotEmpty())
                            <p>Seed {{ $row['seeds']->join(', ') }}</p>
                        @endif
                        @if ($row['singles_rating'])
                            <p>
                                Singles {{ $row['singles_matches'] > 0 ? $row['singles_rating'] : 'Unrated' }}
                                |
                                Doubles {{ $row['doubles_matches'] > 0 ? $row['doubles_rating'] : 'Unrated' }}
                            </p>
                        @endif
                    </div>
                </article>
            @empty
                <p class="py-8 text-center font-bold text-brand-ink/60">{{ $search === '' ? 'No approved players yet.' : 'No players match your search.' }}</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8 rounded-lg bg-brand-surface p-6 shadow-lg">
        <div>
            <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Players by event</p>
            <h2 class="mt-1 text-2xl font-black text-brand-blue">Event entries</h2>
        </div>
        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            @forelse ($entrantsByCategory as $entrants)
                @php($category = $entrants->first()?->category)
                <article class="rounded-lg bg-white p-5 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-xl font-black text-brand-blue">{{ $category?->name ?? 'Unassigned event' }}</h3>
                        <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ $entrants->count() }} entrants</span>
                    </div>
                    <div class="mt-4 divide-y divide-brand-ink/10">
                        @foreach ($entrants as $entrant)
                            <div class="py-3">
                                <p class="font-black text-brand-blue">{{ $entrant->seed ? '#'.$entrant->seed.' ' : '' }}{{ $entrant->displayName() }}</p>
                                <div class="mt-1 space-y-1">
                                    @foreach ($entrant->players->sortBy('position') as $player)
                                        @if ($player->school_name)
                                            <p class="text-sm font-bold text-brand-ink/55">{{ $player->displayName() }} | {{ $player->school_name }}</p>
                                        @endif
                                    @endforeach
                                </div>
                                @if ($entrant->group_name || $entrant->draw_position)
                                    <p class="mt-1 text-xs font-black uppercase text-brand-ink/45">
                                        {{ $entrant->group_name ?: 'Draw' }} {{ $entrant->draw_position ? '#'.$entrant->draw_position : '' }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-lg bg-white p-8 text-center font-bold text-brand-ink/60 lg:col-span-2">
                    {{ $search === '' ? 'No approved event entries yet.' : 'No event entries match your search.' }}
                </div>
            @endforelse
        </div>
    </section>
</div>
