<?php

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

    public function mount(Tournament $tournament, ?string $date = null): void
    {
        $this->tournamentId = $tournament->id;
        $this->date = $date ?: null;
    }

    public function tournament(): Tournament
    {
        return Tournament::with('club', 'organizer', 'categories')->findOrFail($this->tournamentId);
    }

    public function matches(): Collection
    {
        return MatchRecord::query()
            ->with('players.user.playerProfile', 'tournamentCategory')
            ->where('tournament_id', $this->tournamentId)
            ->when($this->date, fn ($query, $date) => $query->whereDate('played_at', $date))
            ->orderByRaw("case when live_status = 'live' then 0 else 1 end")
            ->orderBy('scheduled_at')
            ->orderBy('played_at')
            ->orderBy('tournament_category_id')
            ->get()
            ->filter(fn (MatchRecord $match) => $this->matchMatchesSearch($match))
            ->values();
    }

    public function liveMatches(): Collection
    {
        return $this->matches()->where('live_status', 'live')->values();
    }

    public function dayMatches(): Collection
    {
        return $this->matches()
            ->reject(fn (MatchRecord $match) => $match->live_status === 'live')
            ->groupBy(fn (MatchRecord $match) => $match->played_at->toDateString());
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
        $liveMatches = $this->liveMatches();
        $matches = $this->dayMatches();
        $hasLiveMatches = $liveMatches->isNotEmpty();
    @endphp

    @include('tournaments.partials.nav', ['tournament' => $tournament])

    <form class="mb-6 flex flex-col gap-3 rounded-lg bg-white p-5 shadow-lg sm:flex-row">
        <input name="date" type="date" value="{{ $date }}" class="rounded-md border-brand-ink/10">
        <button class="rounded-md bg-brand-blue px-4 py-2 text-sm font-black uppercase text-white">Filter date</button>
        <a href="{{ route('tournaments.matches', $tournament) }}" class="rounded-md border border-brand-ink/10 px-4 py-2 text-center text-sm font-black uppercase text-brand-blue">Clear date</a>
    </form>

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

        @forelse ($matches as $dateKey => $dayMatches)
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-2xl font-black text-brand-blue">{{ \Illuminate\Support\Carbon::parse($dateKey)->format('M j, Y') }}</h2>
                <div class="mt-5 grid gap-4">
                    @foreach ($dayMatches as $match)
                        @include('tournaments.partials.match-card', ['match' => $match])
                    @endforeach
                </div>
            </section>
        @empty
            @if ($liveMatches->isEmpty())
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-xl font-black text-brand-blue">{{ $search === '' ? 'No matches scheduled' : 'No matches match your search' }}</h2>
                    <p class="mt-2 text-brand-ink/60">{{ $search === '' ? 'Generated draw matches will appear here.' : 'Try another player or school name.' }}</p>
                </section>
            @endif
        @endforelse
    </div>

    @if ($hasLiveMatches)
        <script>
            setTimeout(() => window.location.reload(), 10000);
        </script>
    @endif
</div>
