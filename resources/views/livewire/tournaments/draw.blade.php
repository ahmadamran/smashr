<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Modules\Matches\Models\MatchPlayer;
use Modules\Matches\Models\MatchRecord;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
use Modules\Tournaments\Models\TournamentEntrantPlayer;

new class extends Component
{
    public int $tournamentId;

    public int $categoryId;

    public string $search = '';

    public function mount(Tournament $tournament, TournamentCategory $category): void
    {
        $this->tournamentId = $tournament->id;
        $this->categoryId = $category->id;
    }

    public function tournament(): Tournament
    {
        return Tournament::with('club', 'organizer', 'categories')->findOrFail($this->tournamentId);
    }

    public function category(): TournamentCategory
    {
        return TournamentCategory::with('entrants.players.user.playerProfile', 'matches.players.user.playerProfile')
            ->findOrFail($this->categoryId);
    }

    public function approvedEntrants(): Collection
    {
        return $this->category()->entrants
            ->where('status', 'approved')
            ->sortBy(fn (TournamentEntrant $entrant) => $entrant->draw_position ?? 9999)
            ->values();
    }

    public function filteredEntrants(): Collection
    {
        return $this->approvedEntrants()
            ->filter(fn (TournamentEntrant $entrant) => $this->entrantMatchesSearch($entrant))
            ->values();
    }

    public function drawSize(): int
    {
        $drawSize = 2;

        while ($drawSize < max(2, $this->approvedEntrants()->count())) {
            $drawSize *= 2;
        }

        return $drawSize;
    }

    public function roundCount(): int
    {
        return (int) log($this->drawSize(), 2);
    }

    public function bracketRounds(): array
    {
        $approvedEntrants = $this->approvedEntrants();
        $entrantByPosition = $approvedEntrants->filter(fn ($entrant) => filled($entrant->draw_position))->keyBy('draw_position');
        $matchesByRound = $this->category()->matches
            ->sortBy('draw_position')
            ->groupBy('draw_round')
            ->map(fn ($roundMatches) => $roundMatches->keyBy('draw_position'));
        $drawSize = $this->drawSize();
        $roundCount = $this->roundCount();
        $bracketRounds = [];
        $advancers = [];

        for ($round = 1; $round <= $roundCount; $round++) {
            $matchCount = $drawSize / (2 ** $round);
            $roundMatches = [];

            for ($position = 1; $position <= $matchCount; $position++) {
                $match = $matchesByRound->get($round)?->get($position);
                $winnerSide = $match?->status === 'confirmed' ? $match->winner_side : null;
                $entrantA = null;
                $entrantB = null;

                if ($round === 1) {
                    $entrantA = $entrantByPosition->get(($position * 2) - 1);
                    $entrantB = $entrantByPosition->get($position * 2);
                    $sideA = $match ? $this->sideName($match, 'A') : ($entrantA?->displayName() ?: 'BYE');
                    $sideB = $match ? $this->sideName($match, 'B') : ($entrantB?->displayName() ?: 'BYE');

                    if (! $match && $sideA !== 'BYE' && $sideB === 'BYE') {
                        $winnerSide = 'A';
                    } elseif (! $match && $sideA === 'BYE' && $sideB !== 'BYE') {
                        $winnerSide = 'B';
                    }
                } else {
                    $previousPosition = (($position - 1) * 2) + 1;
                    $sideA = $match ? $this->sideName($match, 'A') : ($advancers[$round - 1][$previousPosition] ?? 'Winner Match '.$previousPosition);
                    $sideB = $match ? $this->sideName($match, 'B') : ($advancers[$round - 1][$previousPosition + 1] ?? 'Winner Match '.($previousPosition + 1));
                }

                $advancer = match ($winnerSide) {
                    'A' => $sideA,
                    'B' => $sideB,
                    default => null,
                };
                if ($advancer === 'BYE') {
                    $advancer = null;
                }
                $advancers[$round][$position] = $advancer;

                $hasPlaceholder = str($sideA)->startsWith('Winner Match') || str($sideB)->startsWith('Winner Match');
                $note = '';
                if (! $match && $round === 1 && $winnerSide && ($sideA === 'BYE' || $sideB === 'BYE')) {
                    $note = 'Advanced by bye';
                } elseif (! $match) {
                    $note = $hasPlaceholder ? 'Pending previous winner' : 'Awaiting match';
                }

                $roundMatches[] = [
                    'position' => $position,
                    'match' => $match,
                    'entrant_a' => $entrantA,
                    'entrant_b' => $entrantB,
                    'side_a' => $sideA,
                    'side_b' => $sideB,
                    'score' => $this->scoreText($match),
                    'winner_side' => $winnerSide,
                    'note' => $note,
                    'matches_search' => $this->drawMatchMatchesSearch($match, $entrantA, $entrantB),
                ];
            }

            $bracketRounds[] = [
                'number' => $round,
                'title' => $this->roundTitle($round),
                'matches' => $roundMatches,
            ];
        }

        return $bracketRounds;
    }

    public function hasBracketSearchResults(): bool
    {
        return collect($this->bracketRounds())
            ->flatMap(fn (array $round) => $round['matches'])
            ->contains('matches_search', true);
    }

    private function sideName(?MatchRecord $match, string $side): string
    {
        if (! $match) {
            return 'TBA';
        }

        return $match->players
            ->where('side', $side)
            ->sortBy('position')
            ->map(fn ($player) => $player->user->playerProfile?->display_name ?? $player->user->name)
            ->filter()
            ->join(' / ') ?: 'TBA';
    }

    private function scoreText(?MatchRecord $match): string
    {
        $games = collect($match?->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game));

        return $games->map(fn ($game) => ((int) $game['a']).'-'.((int) $game['b']))->join(', ');
    }

    private function roundTitle(int $roundNumber): string
    {
        $roundCount = $this->roundCount();
        $drawSize = $this->drawSize();

        if ($roundNumber === $roundCount) {
            return 'Final';
        }

        if ($roundNumber === $roundCount - 1) {
            return 'Semifinals';
        }

        if ($roundNumber === $roundCount - 2) {
            return 'Quarterfinals';
        }

        return 'Round of '.($drawSize / (2 ** ($roundNumber - 1)));
    }

    private function drawMatchMatchesSearch(?MatchRecord $match, ?TournamentEntrant $entrantA, ?TournamentEntrant $entrantB): bool
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return true;
        }

        if ($match) {
            return $match->players->contains(fn (MatchPlayer $player) => str_contains($this->matchPlayerSearchText($player, $match), $search));
        }

        return collect([$entrantA, $entrantB])
            ->filter()
            ->contains(fn (TournamentEntrant $entrant) => $this->entrantMatchesSearch($entrant));
    }

    private function entrantMatchesSearch(TournamentEntrant $entrant): bool
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return true;
        }

        return $entrant->players->contains(fn (TournamentEntrantPlayer $player) => str_contains($this->entrantPlayerSearchText($player), $search));
    }

    private function entrantPlayerSearchText(TournamentEntrantPlayer $player): string
    {
        return Str::lower(collect([
            $player->displayName(),
            $player->display_name,
            $player->user?->name,
            $player->user?->playerProfile?->display_name,
            $player->school_name,
        ])->filter()->join(' '));
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
        $category = $this->category();
        $approvedEntrants = $this->approvedEntrants();
        $filteredEntrants = $this->filteredEntrants();
        $drawSize = $this->drawSize();
        $roundCount = $this->roundCount();
        $bracketRounds = $this->bracketRounds();
    @endphp

    @include('tournaments.partials.nav', ['tournament' => $tournament, 'category' => $category])

    <div class="mb-6 overflow-x-auto">
        <div class="flex min-w-max gap-2">
            @foreach ($tournament->categories->sortBy('name') as $tabCategory)
                <a href="{{ route('tournaments.draw', [$tournament, $tabCategory]) }}" class="rounded-md px-4 py-2 text-xs font-black uppercase {{ $tabCategory->is($category) ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">
                    {{ $tabCategory->name }}
                </a>
            @endforeach
        </div>
    </div>

    @include('tournaments.partials.realtime-search', ['search' => $search, 'id' => 'tournament-draw-search'])

    @if ($category->draw_mode === 'round_robin')
        @include('tournaments.partials.group-tabs', ['tournament' => $tournament, 'category' => $category])
        <section class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Group draw</p>
            <h2 class="mt-1 text-2xl font-black text-brand-blue">Round robin groups of {{ $category->group_size }}</h2>
        </section>
        <div class="grid gap-5 lg:grid-cols-2">
            @forelse ($filteredEntrants->groupBy('group_name')->sortKeys() as $group => $entrants)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-xl font-black text-brand-blue">{{ $group ?: 'Ungrouped' }}</h2>
                        @if ($group)
                            <div class="flex gap-2">
                                <a href="{{ route('tournaments.draw.group', [$tournament, $category, str($group)->slug()]) }}" class="rounded-full bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Standings</a>
                                <a href="{{ route('tournaments.draw.group.matches', [$tournament, $category, str($group)->slug()]) }}" class="rounded-full border border-brand-ink/10 px-4 py-2 text-xs font-black uppercase text-brand-blue">Matches</a>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 divide-y divide-brand-ink/10">
                        @foreach ($entrants as $entrant)
                            <div class="py-2">
                                <p class="font-bold text-brand-ink/70">{{ $entrant->draw_position }}. {{ $entrant->displayName() }}</p>
                                @foreach ($entrant->players->whereNotNull('school_name') as $player)
                                    <p class="mt-1 text-sm font-bold text-brand-ink/50">{{ $player->displayName() }} | {{ $player->school_name }}</p>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="rounded-lg bg-white p-6 text-center font-bold text-brand-ink/60 shadow-lg lg:col-span-2">
                    No draw entries match your search.
                </section>
            @endforelse
        </div>
    @elseif ($category->matches->isEmpty() && $approvedEntrants->isEmpty())
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-xl font-black text-brand-blue">Draw not generated yet</h2>
            <p class="mt-2 text-brand-ink/60">Approved entrants will appear here after the organizer generates the draw.</p>
        </section>
    @elseif ($this->search !== '' && ! $this->hasBracketSearchResults())
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-xl font-black text-brand-blue">No draw entries match your search</h2>
            <p class="mt-2 text-brand-ink/60">Try another player or school name.</p>
        </section>
    @else
        <section class="rounded-lg bg-white p-5 shadow-lg">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-ink/10 pb-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Main draw</p>
                    <h2 class="text-2xl font-black text-brand-blue">{{ $drawSize }} draw | {{ $roundCount }} rounds</h2>
                </div>
                <a href="{{ route('tournaments.matches', $tournament, ['date' => request('date')]) }}" class="rounded-full bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Match schedule</a>
            </div>

            <div class="mt-5 overflow-x-auto pb-3">
                <div class="grid min-w-max auto-cols-[280px] grid-flow-col gap-5">
                    @foreach ($bracketRounds as $roundIndex => $round)
                        <div>
                            <div class="sticky left-0 rounded-md bg-brand-blue px-4 py-3 text-white">
                                <p class="text-[11px] font-black uppercase tracking-[.18em] text-brand-mist">Round {{ $round['number'] }}</p>
                                <h3 class="mt-1 text-lg font-black">{{ $round['title'] }}</h3>
                            </div>

                            <div class="mt-4 grid" style="grid-template-rows: repeat({{ $drawSize }}, 6.75rem);">
                                @foreach ($round['matches'] as $drawMatch)
                                    @continue($this->search !== '' && ! $drawMatch['matches_search'])
                                    @php
                                        $hasPreviousRound = $roundIndex > 0;
                                        $hasNextRound = $roundIndex < count($bracketRounds) - 1;
                                        $rowSpan = 2 ** $round['number'];
                                        $rowStart = (($drawMatch['position'] - 1) * $rowSpan) + 1;
                                    @endphp
                                    <div class="relative flex items-center" style="grid-row: {{ $rowStart }} / span {{ $rowSpan }};">
                                        @if ($hasPreviousRound)
                                            <span class="pointer-events-none absolute right-[calc(100%+0.625rem)] top-1/4 bottom-1/4 w-px bg-brand-ink/25"></span>
                                            <span class="pointer-events-none absolute right-full top-1/2 h-px w-2.5 bg-brand-ink/25"></span>
                                        @endif
                                        @if ($hasNextRound)
                                            <span class="pointer-events-none absolute left-full top-1/2 h-px w-2.5 bg-brand-ink/25"></span>
                                        @endif
                                        <article class="w-full rounded-md border border-brand-ink/10 bg-brand-surface p-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-[11px] font-black uppercase tracking-[.16em] text-brand-ink/45">Match {{ $drawMatch['position'] }}</p>
                                                @if ($drawMatch['match']?->scheduled_at || $drawMatch['match']?->court_label)
                                                    <p class="text-[11px] font-black uppercase text-brand-green">
                                                        {{ $drawMatch['match']?->scheduled_at?->format('g:i A') }}
                                                        {{ $drawMatch['match']?->court_label ? ' | '.$drawMatch['match']->court_label : '' }}
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="mt-3 divide-y divide-brand-ink/10 overflow-hidden rounded-md border border-brand-ink/10 bg-white">
                                                <div class="flex min-h-12 items-center justify-between gap-3 px-3 py-2 {{ $drawMatch['winner_side'] === 'A' ? 'bg-brand-mist' : '' }}">
                                                    <p class="text-sm font-black text-brand-blue">{{ $drawMatch['side_a'] }}</p>
                                                    @if ($drawMatch['winner_side'] === 'A')
                                                        <span class="text-xs font-black uppercase text-green-700">Won</span>
                                                    @endif
                                                </div>
                                                <div class="flex min-h-12 items-center justify-between gap-3 px-3 py-2 {{ $drawMatch['winner_side'] === 'B' ? 'bg-brand-mist' : '' }}">
                                                    <p class="text-sm font-black text-brand-blue">{{ $drawMatch['side_b'] }}</p>
                                                    @if ($drawMatch['winner_side'] === 'B')
                                                        <span class="text-xs font-black uppercase text-green-700">Won</span>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($drawMatch['score'])
                                                <p class="mt-2 text-xs font-bold text-brand-ink/60">{{ $drawMatch['score'] }}</p>
                                            @elseif ($drawMatch['note'])
                                                <p class="mt-2 text-xs font-bold text-brand-ink/45">{{ $drawMatch['note'] }}</p>
                                            @endif
                                        </article>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
