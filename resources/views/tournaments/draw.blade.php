@php
    $approvedEntrants = $category->entrants
        ->where('status', 'approved')
        ->sortBy(fn ($entrant) => $entrant->draw_position ?? 9999)
        ->values();
    $entrantByPosition = $approvedEntrants->filter(fn ($entrant) => filled($entrant->draw_position))->keyBy('draw_position');
    $matchesByRound = $category->matches
        ->sortBy('draw_position')
        ->groupBy('draw_round')
        ->map(fn ($roundMatches) => $roundMatches->keyBy('draw_position'));

    $drawSize = 2;
    while ($drawSize < max(2, $approvedEntrants->count())) {
        $drawSize *= 2;
    }

    $roundCount = (int) log($drawSize, 2);
    $roundTitle = function (int $roundNumber) use ($roundCount, $drawSize): string {
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
    };

    $sideName = function ($match, string $side): string {
        if (! $match) {
            return 'TBA';
        }

        return $match->players
            ->where('side', $side)
            ->sortBy('position')
            ->map(fn ($player) => $player->user->playerProfile?->display_name ?? $player->user->name)
            ->filter()
            ->join(' / ') ?: 'TBA';
    };

    $scoreText = function ($match): string {
        $games = collect($match?->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game));

        return $games->map(fn ($game) => ((int) $game['a']).'-'.((int) $game['b']))->join(', ');
    };

    $bracketRounds = [];
    $advancers = [];
    for ($round = 1; $round <= $roundCount; $round++) {
        $matchCount = $drawSize / (2 ** $round);
        $roundMatches = [];

        for ($position = 1; $position <= $matchCount; $position++) {
            $match = $matchesByRound->get($round)?->get($position);
            $winnerSide = $match?->status === 'confirmed' ? $match->winner_side : null;

            if ($round === 1) {
                $entrantA = $entrantByPosition->get(($position * 2) - 1);
                $entrantB = $entrantByPosition->get($position * 2);
                $sideA = $match ? $sideName($match, 'A') : ($entrantA?->displayName() ?: 'BYE');
                $sideB = $match ? $sideName($match, 'B') : ($entrantB?->displayName() ?: 'BYE');

                if (! $match && $sideA !== 'BYE' && $sideB === 'BYE') {
                    $winnerSide = 'A';
                } elseif (! $match && $sideA === 'BYE' && $sideB !== 'BYE') {
                    $winnerSide = 'B';
                }
            } else {
                $previousPosition = (($position - 1) * 2) + 1;
                $sideA = $match ? $sideName($match, 'A') : ($advancers[$round - 1][$previousPosition] ?? 'Winner Match '.$previousPosition);
                $sideB = $match ? $sideName($match, 'B') : ($advancers[$round - 1][$previousPosition + 1] ?? 'Winner Match '.($previousPosition + 1));
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
                'side_a' => $sideA,
                'side_b' => $sideB,
                'score' => $scoreText($match),
                'winner_side' => $winnerSide,
                'note' => $note,
            ];
        }

        $bracketRounds[] = [
            'number' => $round,
            'title' => $roundTitle($round),
            'matches' => $roundMatches,
        ];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-[#071a80]">{{ $category->name }} draw</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('tournaments.partials.nav', ['tournament' => $tournament, 'category' => $category])

        <div class="mb-6 overflow-x-auto">
            <div class="flex min-w-max gap-2">
                @foreach ($tournament->categories->sortBy('name') as $tabCategory)
                    <a href="{{ route('tournaments.draw', [$tournament, $tabCategory]) }}" class="rounded-md px-4 py-2 text-xs font-black uppercase {{ $tabCategory->is($category) ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">
                        {{ $tabCategory->name }}
                    </a>
                @endforeach
            </div>
        </div>

        @if ($category->draw_mode === 'round_robin')
            @include('tournaments.partials.group-tabs', ['tournament' => $tournament, 'category' => $category])
            <section class="mb-6 rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Group draw</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">Round robin groups of {{ $category->group_size }}</h2>
            </section>
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($category->entrants->where('status', 'approved')->groupBy('group_name')->sortKeys() as $group => $entrants)
                    <section class="rounded-lg bg-white p-6 shadow-lg">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-black text-[#071a80]">{{ $group ?: 'Ungrouped' }}</h2>
                            @if ($group)
                                <div class="flex gap-2">
                                    <a href="{{ route('tournaments.draw.group', [$tournament, $category, str($group)->slug()]) }}" class="rounded-full bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Standings</a>
                                    <a href="{{ route('tournaments.draw.group.matches', [$tournament, $category, str($group)->slug()]) }}" class="rounded-full border border-blue-950/10 px-4 py-2 text-xs font-black uppercase text-[#071a80]">Matches</a>
                                </div>
                            @endif
                        </div>
                        <div class="mt-4 divide-y divide-blue-950/10">
                            @foreach ($entrants as $entrant)
                                <p class="py-2 font-bold text-blue-950/70">{{ $entrant->draw_position }}. {{ $entrant->displayName() }}</p>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @elseif ($category->matches->isEmpty() && $approvedEntrants->isEmpty())
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-xl font-black text-[#071a80]">Draw not generated yet</h2>
                <p class="mt-2 text-blue-950/60">Approved entrants will appear here after the organizer generates the draw.</p>
            </section>
        @else
            <section class="rounded-lg bg-white p-5 shadow-lg">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-blue-950/10 pb-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Main draw</p>
                        <h2 class="text-2xl font-black text-[#071a80]">{{ $drawSize }} draw | {{ $roundCount }} rounds</h2>
                    </div>
                    <a href="{{ route('tournaments.matches', $tournament, ['date' => request('date')]) }}" class="rounded-full bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Match schedule</a>
                </div>

                <div class="mt-5 overflow-x-auto pb-3">
                    <div class="grid min-w-max auto-cols-[280px] grid-flow-col gap-5">
                        @foreach ($bracketRounds as $roundIndex => $round)
                            <div>
                                <div class="sticky left-0 rounded-md bg-[#071a80] px-4 py-3 text-white">
                                    <p class="text-[11px] font-black uppercase tracking-[.18em] text-[#d6a31d]">Round {{ $round['number'] }}</p>
                                    <h3 class="mt-1 text-lg font-black">{{ $round['title'] }}</h3>
                                </div>

                                <div class="mt-4 grid" style="grid-template-rows: repeat({{ $drawSize }}, 6.75rem);">
                                    @foreach ($round['matches'] as $drawMatch)
                                        @php
                                            $hasPreviousRound = $roundIndex > 0;
                                            $hasNextRound = $roundIndex < count($bracketRounds) - 1;
                                            $rowSpan = 2 ** $round['number'];
                                            $rowStart = (($drawMatch['position'] - 1) * $rowSpan) + 1;
                                        @endphp
                                        <div class="relative flex items-center" style="grid-row: {{ $rowStart }} / span {{ $rowSpan }};">
                                            @if ($hasPreviousRound)
                                                <span class="pointer-events-none absolute right-[calc(100%+0.625rem)] top-1/4 bottom-1/4 w-px bg-blue-950/25"></span>
                                                <span class="pointer-events-none absolute right-full top-1/2 h-px w-2.5 bg-blue-950/25"></span>
                                            @endif
                                            @if ($hasNextRound)
                                                <span class="pointer-events-none absolute left-full top-1/2 h-px w-2.5 bg-blue-950/25"></span>
                                            @endif
                                            <article class="w-full rounded-md border border-blue-950/10 bg-[#f8fafc] p-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-[11px] font-black uppercase tracking-[.16em] text-blue-950/45">Match {{ $drawMatch['position'] }}</p>
                                                    @if ($drawMatch['match']?->scheduled_at || $drawMatch['match']?->court_label)
                                                        <p class="text-[11px] font-black uppercase text-[#d6a31d]">
                                                            {{ $drawMatch['match']?->scheduled_at?->format('g:i A') }}
                                                            {{ $drawMatch['match']?->court_label ? ' | '.$drawMatch['match']->court_label : '' }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <div class="mt-3 divide-y divide-blue-950/10 overflow-hidden rounded-md border border-blue-950/10 bg-white">
                                                    <div class="flex min-h-12 items-center justify-between gap-3 px-3 py-2 {{ $drawMatch['winner_side'] === 'A' ? 'bg-blue-50' : '' }}">
                                                        <p class="text-sm font-black text-[#071a80]">{{ $drawMatch['side_a'] }}</p>
                                                        @if ($drawMatch['winner_side'] === 'A')
                                                            <span class="text-xs font-black uppercase text-green-700">Won</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex min-h-12 items-center justify-between gap-3 px-3 py-2 {{ $drawMatch['winner_side'] === 'B' ? 'bg-blue-50' : '' }}">
                                                        <p class="text-sm font-black text-[#071a80]">{{ $drawMatch['side_b'] }}</p>
                                                        @if ($drawMatch['winner_side'] === 'B')
                                                            <span class="text-xs font-black uppercase text-green-700">Won</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if ($drawMatch['score'])
                                                    <p class="mt-2 text-xs font-bold text-blue-950/60">{{ $drawMatch['score'] }}</p>
                                                @elseif ($drawMatch['note'])
                                                    <p class="mt-2 text-xs font-bold text-blue-950/45">{{ $drawMatch['note'] }}</p>
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
</x-app-layout>
