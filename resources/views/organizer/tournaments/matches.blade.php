<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="mb-6 rounded bg-red-50 p-4 font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="mb-6 rounded-lg bg-white p-5 shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form class="grid flex-1 gap-3 md:grid-cols-[minmax(14rem,1fr)_auto_auto]">
                    @if ($selectedDate)
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                    @endif
                    <label class="text-xs font-black uppercase tracking-[.18em] text-brand-green">
                        Search
                        <input name="search" value="{{ request('search') }}" placeholder="Search player or school" class="mt-2 w-full rounded-md border-brand-ink/10 text-sm font-bold normal-case tracking-normal text-brand-ink">
                    </label>
                    <div class="flex items-end">
                        <button class="w-full rounded-md bg-brand-blue px-4 py-3 text-xs font-black uppercase text-white">Filter</button>
                    </div>
                    @if (request()->hasAny(['search', 'date']))
                        <div class="flex items-end">
                            <a href="{{ route('organizer.tournaments.matches', $tournament) }}" class="w-full rounded-md border border-brand-ink/10 px-4 py-3 text-center text-xs font-black uppercase text-brand-blue">Clear</a>
                        </div>
                    @endif
                </form>
                <div class="text-sm font-bold text-brand-ink/60">
                    {{ $matches->count() }} {{ \Illuminate\Support\Str::plural('match', $matches->count()) }}
                </div>
            </div>

            @if ($matchDates->isNotEmpty())
                <div class="mt-5 overflow-x-auto">
                    <div class="flex min-w-max gap-2">
                        <a href="{{ route('organizer.tournaments.matches', ['tournament' => $tournament, 'search' => request('search')]) }}" class="rounded-md border px-4 py-3 text-left {{ $selectedDate === null ? 'border-brand-blue bg-brand-blue text-white' : 'border-brand-ink/10 bg-brand-surface text-brand-blue hover:bg-brand-mist' }}">
                            <span class="block text-[11px] font-black uppercase tracking-[.16em] {{ $selectedDate === null ? 'text-brand-mist' : 'text-brand-green' }}">All</span>
                            <span class="mt-1 block text-sm font-black">Dates</span>
                        </a>
                        @foreach ($matchDates as $matchDate)
                            <a href="{{ route('organizer.tournaments.matches', ['tournament' => $tournament, 'date' => $matchDate, 'search' => request('search')]) }}" class="rounded-md border px-4 py-3 text-left {{ $selectedDate === $matchDate ? 'border-brand-blue bg-brand-blue text-white' : 'border-brand-ink/10 bg-brand-surface text-brand-blue hover:bg-brand-mist' }}">
                                <span class="block text-[11px] font-black uppercase tracking-[.16em] {{ $selectedDate === $matchDate ? 'text-brand-mist' : 'text-brand-green' }}">{{ \Illuminate\Support\Carbon::parse($matchDate)->format('D') }}</span>
                                <span class="mt-1 block text-sm font-black">{{ \Illuminate\Support\Carbon::parse($matchDate)->format('M j') }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <div class="grid gap-4">
            @forelse ($groupedMatches as $matchDate => $dateMatches)
                <section class="grid gap-4">
                    <div class="sticky top-0 z-10 rounded-lg border border-brand-ink/10 bg-brand-surface/95 px-5 py-3 backdrop-blur">
                        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">{{ $matchDate === 'unscheduled' ? 'Date TBA' : \Illuminate\Support\Carbon::parse($matchDate)->format('l') }}</p>
                        <h2 class="text-2xl font-black text-brand-blue">{{ $matchDate === 'unscheduled' ? 'Unscheduled matches' : \Illuminate\Support\Carbon::parse($matchDate)->format('M j, Y') }}</h2>
                    </div>

                    @foreach ($dateMatches as $match)
                        @php
                            $sideA = $match->players->where('side', 'A')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
                            $sideB = $match->players->where('side', 'B')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
                            $games = collect($match->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
                            $liveScore = $match->live_score ?? [];
                            $liveGames = collect($liveScore['games'] ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
                            $current = $liveScore['current'] ?? ['a' => 0, 'b' => 0];
                        @endphp
                        <article class="rounded-lg bg-white p-5 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">{{ $match->tournamentCategory?->name ?? 'Tournament match' }} | {{ str_replace('_', ' ', $match->status) }}</p>
                            <h2 class="mt-2 text-xl font-black text-brand-blue">{{ ($match->scheduled_at ?? $match->played_at)->format('M j, Y') }}</h2>
                            @if ($match->scheduled_at || $match->court_label)
                                <div class="mt-2 flex flex-wrap gap-2 text-xs font-black uppercase text-brand-blue">
                                    @if ($match->scheduled_at)
                                        <span class="rounded-full bg-brand-surface px-3 py-1">{{ $match->scheduled_at->format('g:i A') }}</span>
                                    @endif
                                    @if ($match->court_label)
                                        <span class="rounded-full bg-brand-surface px-3 py-1">{{ $match->court_label }}</span>
                                    @endif
                                    @if ($match->estimated_duration_minutes)
                                        <span class="rounded-full bg-brand-surface px-3 py-1">{{ $match->estimated_duration_minutes }} min</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($match->score_sheet_token)
                                <a href="{{ route('scoresheets.show', $match->score_sheet_token) }}" class="rounded-full bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Score sheet</a>
                            @endif
                            @if ($match->live_status === 'live')
                                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">Live</span>
                            @elseif ($match->live_status === 'submitted')
                                <span class="rounded-full bg-brand-green px-3 py-1 text-xs font-black uppercase text-white">Review</span>
                            @endif
                            <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ $match->draw_group ?: 'Round '.$match->draw_round }}</span>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <p class="rounded-md border border-brand-ink/10 p-3 font-bold text-brand-ink/70 {{ $match->winner_side === 'A' && $games->isNotEmpty() ? 'bg-brand-mist' : '' }}">A: {{ $sideA }}</p>
                        <p class="rounded-md border border-brand-ink/10 p-3 font-bold text-brand-ink/70 {{ $match->winner_side === 'B' && $games->isNotEmpty() ? 'bg-brand-mist' : '' }}">B: {{ $sideB }}</p>
                    </div>

                    @if ($match->live_status === 'live')
                        <div class="mt-4 rounded-md bg-brand-blue p-4 text-white">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-mist">Live score | Game {{ $liveScore['current_game'] ?? 1 }}</p>
                            <p class="mt-2 text-3xl font-black">A {{ (int) ($current['a'] ?? 0) }} - {{ (int) ($current['b'] ?? 0) }} B</p>
                            @if ($liveGames->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($liveGames as $index => $game)
                                        <span class="rounded-full bg-white/10 px-3 py-1 text-sm font-black">G{{ $index + 1 }} {{ (int) $game['a'] }}-{{ (int) $game['b'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @elseif ($match->live_status === 'submitted')
                        <div class="mt-4 rounded-md border border-brand-green/40 bg-brand-mist p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Scoresheet submitted</p>
                                    <p class="mt-1 text-sm font-bold text-brand-ink/60">Review the umpire score before it becomes official.</p>
                                </div>
                                <form method="POST" action="{{ route('organizer.tournaments.matches.approve-live-score', [$tournament, $match]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-full bg-green-700 px-4 py-2 text-xs font-black uppercase text-white">Approve result</button>
                                </form>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                @foreach ($liveGames as $index => $game)
                                    <div class="rounded-md bg-white px-3 py-2">
                                        <p class="text-[11px] font-black uppercase text-brand-ink/45">Game {{ $index + 1 }}</p>
                                        <p class="mt-1 text-2xl font-black text-brand-blue">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($match->status === 'confirmed' && $games->isNotEmpty())
                        <div class="mt-4 rounded-md bg-brand-blue p-4 text-white">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-mist">Submitted result</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                @foreach ($games as $index => $game)
                                    <div class="rounded-md bg-white/10 px-3 py-2">
                                        <p class="text-[11px] font-black uppercase text-white/60">Game {{ $index + 1 }}</p>
                                        <p class="mt-1 text-2xl font-black">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('organizer.tournaments.matches.result', [$tournament, $match]) }}" class="mt-5 rounded-md border border-brand-ink/10 bg-brand-surface p-4">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 md:grid-cols-3">
                                <label class="text-sm font-black text-brand-blue">
                                    Played date
                                    <input type="date" name="played_at" value="{{ old('played_at', $match->played_at?->toDateString() ?? now()->toDateString()) }}" class="mt-1 w-full rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                                </label>
                                <label class="text-sm font-black text-brand-blue">
                                    Winner
                                    <select name="winner_side" class="mt-1 w-full rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                                        <option value="A" @selected(old('winner_side', $match->winner_side) === 'A')>Side A</option>
                                        <option value="B" @selected(old('winner_side', $match->winner_side) === 'B')>Side B</option>
                                    </select>
                                </label>
                                <div class="flex items-end">
                                    <button class="w-full rounded-md bg-brand-blue px-4 py-3 text-xs font-black uppercase text-white">Save result</button>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                @for ($game = 0; $game < 3; $game++)
                                    @php($existingGame = $games->get($game, []))
                                    <div class="rounded-md bg-white p-3">
                                        <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Game {{ $game + 1 }}</p>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <label class="text-xs font-black uppercase text-brand-ink/50">
                                                Side A
                                                <input type="number" min="0" max="30" name="games[{{ $game }}][a]" value="{{ old("games.$game.a", $existingGame['a'] ?? '') }}" class="mt-1 w-full rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                                            </label>
                                            <label class="text-xs font-black uppercase text-brand-ink/50">
                                                Side B
                                                <input type="number" min="0" max="30" name="games[{{ $game }}][b]" value="{{ old("games.$game.b", $existingGame['b'] ?? '') }}" class="mt-1 w-full rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                                            </label>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </form>
                    @endif
                        </article>
                    @endforeach
                </section>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <h2 class="text-xl font-black text-brand-blue">{{ request()->hasAny(['search', 'date']) ? 'No matches found' : 'No matches yet' }}</h2>
                    <p class="mt-2 text-brand-ink/60">{{ request()->hasAny(['search', 'date']) ? 'Try another player, school, or match date.' : 'Generate a draw to create tournament matches.' }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
