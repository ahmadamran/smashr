<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Modules\Matches\Models\MatchPlayer;
use Modules\Matches\Models\MatchRecord;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentEntrantPlayer;

new class extends Component
{
    public int $tournamentId;

    public string $search = '';

    public ?string $date = null;

    public ?string $view = 'list';

    public function mount(Tournament $tournament, ?string $date = null, ?string $view = null): void
    {
        $this->tournamentId = $tournament->id;
        $this->date = $date ?: null;
        $this->view = in_array($view, ['list', 'grid'], true) ? $view : 'list';
    }

    public function tournament(): Tournament
    {
        return Tournament::with('club', 'organizer', 'categories')->findOrFail($this->tournamentId);
    }

    public function matchDates(): Collection
    {
        return MatchRecord::query()
            ->where('tournament_id', $this->tournamentId)
            ->whereNotNull('played_at')
            ->selectRaw('date(played_at) as match_date')
            ->distinct()
            ->orderBy('match_date')
            ->pluck('match_date')
            ->map(fn ($date) => (string) $date)
            ->values();
    }

    public function selectedDate(): ?string
    {
        $dates = $this->matchDates();

        if ($dates->isEmpty()) {
            return null;
        }

        if ($this->date && $dates->contains($this->date)) {
            return $this->date;
        }

        $today = now()->toDateString();

        if ($dates->contains($today)) {
            return $today;
        }

        if ($today > $dates->last()) {
            return $dates->last();
        }

        return $dates->first();
    }

    public function matchesForSelectedDate(): Collection
    {
        $selectedDate = $this->selectedDate();

        if (! $selectedDate) {
            return collect();
        }

        return MatchRecord::query()
            ->with('players.user.playerProfile', 'tournamentCategory')
            ->where('tournament_id', $this->tournamentId)
            ->whereDate('played_at', $selectedDate)
            ->where('live_status', '!=', 'live')
            ->orderBy('scheduled_at')
            ->orderBy('played_at')
            ->orderBy('tournament_category_id')
            ->get()
            ->filter(fn (MatchRecord $match) => $this->matchMatchesSearch($match))
            ->values();
    }

    public function liveMatches(): Collection
    {
        return MatchRecord::query()
            ->with('players.user.playerProfile', 'tournamentCategory')
            ->where('tournament_id', $this->tournamentId)
            ->where('live_status', 'live')
            ->orderBy('scheduled_at')
            ->orderBy('played_at')
            ->orderBy('tournament_category_id')
            ->get()
            ->filter(fn (MatchRecord $match) => $this->matchMatchesSearch($match))
            ->values();
    }

    public function gridMatches(): Collection
    {
        return $this->matchesForSelectedDate()
            ->groupBy(fn (MatchRecord $match) => $match->scheduled_at?->format('g:i A') ?? 'Time TBA');
    }

    public function sideName(MatchRecord $match, string $side): string
    {
        return $match->players
            ->where('side', $side)
            ->sortBy('position')
            ->map(fn (MatchPlayer $player) => $player->user?->playerProfile?->display_name ?? $player->user?->name)
            ->filter()
            ->join(' / ') ?: 'TBA';
    }

    public function scoreSummary(MatchRecord $match): string
    {
        $games = collect($match->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game));

        if ($games->isEmpty()) {
            return 'Score pending';
        }

        return $games->map(fn ($game) => ((int) $game['a']).'-'.((int) $game['b']))->join(', ');
    }

    private function matchMatchesSearch(MatchRecord $match): bool
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return true;
        }

        return $match->players->contains(fn (MatchPlayer $player) => str_contains($this->matchPlayerSearchText($player, $match), $search));
    }

    private function matchPlayerSearchText(MatchPlayer $player, MatchRecord $match): string
    {
        return Str::lower(collect([
            $player->user?->playerProfile?->display_name,
            $player->user?->name,
            $this->schoolName($player, $match),
        ])->filter()->join(' '));
    }

    private function schoolName(MatchPlayer $player, MatchRecord $match): ?string
    {
        if (! $player->user_id || ! $match->tournament_category_id) {
            return null;
        }

        return TournamentEntrantPlayer::query()
            ->where('user_id', $player->user_id)
            ->whereHas('entrant', fn ($query) => $query
                ->where('tournament_id', $this->tournamentId)
                ->where('tournament_category_id', $match->tournament_category_id)
                ->where('status', 'approved'))
            ->value('school_name');
    }

    private function normalizedSearch(): string
    {
        return Str::lower(trim($this->search));
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    @php
        $tournament = $this->tournament();
        $matchDates = $this->matchDates();
        $selectedDate = $this->selectedDate();
        $liveMatches = $this->liveMatches();
        $matches = $this->matchesForSelectedDate();
        $gridMatches = $this->gridMatches();
        $hasLiveMatches = $liveMatches->isNotEmpty();
    @endphp

    @include('tournaments.partials.nav', ['tournament' => $tournament])

    <section class="mb-6 rounded-lg bg-white p-5 shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Match dates</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">
                    {{ $selectedDate ? Carbon::parse($selectedDate)->format('M j, Y') : 'No match dates yet' }}
                </h2>
            </div>

            <div class="flex w-fit overflow-hidden rounded-md border border-brand-ink/10 bg-brand-surface p-1">
                <a href="{{ route('tournaments.matches', ['tournament' => $tournament, 'date' => $selectedDate, 'view' => 'list']) }}" class="rounded px-4 py-2 text-xs font-black uppercase {{ $view === 'list' ? 'bg-brand-blue text-white' : 'text-brand-blue' }}">List</a>
                <a href="{{ route('tournaments.matches', ['tournament' => $tournament, 'date' => $selectedDate, 'view' => 'grid']) }}" class="rounded px-4 py-2 text-xs font-black uppercase {{ $view === 'grid' ? 'bg-brand-blue text-white' : 'text-brand-blue' }}">Grid</a>
            </div>
        </div>

        @if ($matchDates->isNotEmpty())
            <div class="mt-5 overflow-x-auto">
                <div class="flex min-w-max gap-2">
                    @foreach ($matchDates as $matchDate)
                        <a href="{{ route('tournaments.matches', ['tournament' => $tournament, 'date' => $matchDate, 'view' => $view]) }}" class="rounded-md border px-4 py-3 text-left {{ $selectedDate === $matchDate ? 'border-brand-blue bg-brand-blue text-white' : 'border-brand-ink/10 bg-white text-brand-blue hover:bg-brand-surface' }}">
                            <span class="block text-[11px] font-black uppercase tracking-[.16em] {{ $selectedDate === $matchDate ? 'text-brand-mist' : 'text-brand-green' }}">{{ Carbon::parse($matchDate)->format('D') }}</span>
                            <span class="mt-1 block text-sm font-black">{{ Carbon::parse($matchDate)->format('M j') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @include('tournaments.partials.realtime-search', ['search' => $search, 'id' => 'tournament-match-search'])

    <div class="grid gap-6">
        @if ($liveMatches->isNotEmpty())
            <section id="live" class="rounded-lg bg-white p-6 shadow-lg ring-2 ring-red-600/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.2em] text-red-600">Live now</p>
                        <h2 class="text-2xl font-black text-brand-blue">Live matches</h2>
                    </div>
                    <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">{{ $liveMatches->count() }} live</span>
                </div>

                <div class="mt-5 grid gap-4">
                    @foreach ($liveMatches as $match)
                        @include('tournaments.partials.match-card', ['match' => $match])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($matches->isNotEmpty())
            @if ($view === 'grid')
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-brand-blue">Grid view</h2>
                    <div class="mt-5 grid gap-5">
                        @foreach ($gridMatches as $time => $timeMatches)
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">{{ $time }}</p>
                                <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($timeMatches as $match)
                                        <article class="rounded-md border border-brand-ink/10 bg-brand-surface p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-[11px] font-black uppercase text-brand-green">{{ $match->tournamentCategory?->name ?? 'Tournament match' }}</p>
                                                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase text-brand-blue">{{ $match->court_label ?: 'Court TBA' }}</span>
                                            </div>
                                            <p class="mt-3 text-sm font-black text-brand-blue">A: {{ $this->sideName($match, 'A') }}</p>
                                            <p class="mt-1 text-sm font-black text-brand-blue">B: {{ $this->sideName($match, 'B') }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-black uppercase text-brand-ink/55">
                                                <span>{{ str_replace('_', ' ', $match->status) }}</span>
                                                <span>{{ $this->scoreSummary($match) }}</span>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-brand-blue">{{ Carbon::parse($selectedDate)->format('M j, Y') }}</h2>
                    <div class="mt-5 grid gap-4">
                        @foreach ($matches as $match)
                            @include('tournaments.partials.match-card', ['match' => $match])
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            @if ($liveMatches->isEmpty())
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-xl font-black text-brand-blue">{{ $search === '' ? 'No matches scheduled' : 'No matches match your search' }}</h2>
                    <p class="mt-2 text-brand-ink/60">{{ $search === '' ? 'Generated draw matches will appear here.' : 'Try another player or school name.' }}</p>
                </section>
            @endif
        @endif
    </div>

    @if ($hasLiveMatches)
        <script>
            setTimeout(() => window.location.reload(), 10000);
        </script>
    @endif
</div>
