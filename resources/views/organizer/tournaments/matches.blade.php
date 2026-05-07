<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Matches | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="mb-6 rounded bg-red-50 p-4 font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-4">
            @forelse ($matches as $match)
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
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ $match->tournamentCategory?->name ?? 'Tournament match' }} | {{ str_replace('_', ' ', $match->status) }}</p>
                            <h2 class="mt-2 text-xl font-black text-[#071a80]">{{ ($match->scheduled_at ?? $match->played_at)->format('M j, Y') }}</h2>
                            @if ($match->scheduled_at || $match->court_label)
                                <div class="mt-2 flex flex-wrap gap-2 text-xs font-black uppercase text-[#071a80]">
                                    @if ($match->scheduled_at)
                                        <span class="rounded-full bg-[#f3f6fb] px-3 py-1">{{ $match->scheduled_at->format('g:i A') }}</span>
                                    @endif
                                    @if ($match->court_label)
                                        <span class="rounded-full bg-[#f3f6fb] px-3 py-1">{{ $match->court_label }}</span>
                                    @endif
                                    @if ($match->estimated_duration_minutes)
                                        <span class="rounded-full bg-[#f3f6fb] px-3 py-1">{{ $match->estimated_duration_minutes }} min</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($match->score_sheet_token)
                                <a href="{{ route('scoresheets.show', $match->score_sheet_token) }}" class="rounded-full bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Score sheet</a>
                            @endif
                            @if ($match->live_status === 'live')
                                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">Live</span>
                            @elseif ($match->live_status === 'submitted')
                                <span class="rounded-full bg-[#d6a31d] px-3 py-1 text-xs font-black uppercase text-[#071a80]">Review</span>
                            @endif
                            <span class="rounded-full bg-[#f3f6fb] px-3 py-1 text-xs font-black uppercase text-[#071a80]">{{ $match->draw_group ?: 'Round '.$match->draw_round }}</span>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <p class="rounded-md border border-blue-950/10 p-3 font-bold text-blue-950/70 {{ $match->winner_side === 'A' && $games->isNotEmpty() ? 'bg-blue-50' : '' }}">A: {{ $sideA }}</p>
                        <p class="rounded-md border border-blue-950/10 p-3 font-bold text-blue-950/70 {{ $match->winner_side === 'B' && $games->isNotEmpty() ? 'bg-blue-50' : '' }}">B: {{ $sideB }}</p>
                    </div>

                    @if ($match->live_status === 'live')
                        <div class="mt-4 rounded-md bg-[#071a80] p-4 text-white">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-[#d6a31d]">Live score | Game {{ $liveScore['current_game'] ?? 1 }}</p>
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
                        <div class="mt-4 rounded-md border border-[#d6a31d]/40 bg-[#fff8e6] p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[.18em] text-[#d6a31d]">Scoresheet submitted</p>
                                    <p class="mt-1 text-sm font-bold text-blue-950/60">Review the umpire score before it becomes official.</p>
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
                                        <p class="text-[11px] font-black uppercase text-blue-950/45">Game {{ $index + 1 }}</p>
                                        <p class="mt-1 text-2xl font-black text-[#071a80]">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($match->status === 'confirmed' && $games->isNotEmpty())
                        <div class="mt-4 rounded-md bg-[#071a80] p-4 text-white">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-[#d6a31d]">Submitted result</p>
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
                        <form method="POST" action="{{ route('organizer.tournaments.matches.result', [$tournament, $match]) }}" class="mt-5 rounded-md border border-blue-950/10 bg-[#f8fafc] p-4">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 md:grid-cols-3">
                                <label class="text-sm font-black text-[#071a80]">
                                    Played date
                                    <input type="date" name="played_at" value="{{ old('played_at', $match->played_at?->toDateString() ?? now()->toDateString()) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                                </label>
                                <label class="text-sm font-black text-[#071a80]">
                                    Winner
                                    <select name="winner_side" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                                        <option value="A" @selected(old('winner_side', $match->winner_side) === 'A')>Side A</option>
                                        <option value="B" @selected(old('winner_side', $match->winner_side) === 'B')>Side B</option>
                                    </select>
                                </label>
                                <div class="flex items-end">
                                    <button class="w-full rounded-md bg-[#071a80] px-4 py-3 text-xs font-black uppercase text-white">Save result</button>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                @for ($game = 0; $game < 3; $game++)
                                    @php($existingGame = $games->get($game, []))
                                    <div class="rounded-md bg-white p-3">
                                        <p class="text-xs font-black uppercase tracking-[.18em] text-[#d6a31d]">Game {{ $game + 1 }}</p>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <label class="text-xs font-black uppercase text-blue-950/50">
                                                Side A
                                                <input type="number" min="0" max="30" name="games[{{ $game }}][a]" value="{{ old("games.$game.a", $existingGame['a'] ?? '') }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                                            </label>
                                            <label class="text-xs font-black uppercase text-blue-950/50">
                                                Side B
                                                <input type="number" min="0" max="30" name="games[{{ $game }}][b]" value="{{ old("games.$game.b", $existingGame['b'] ?? '') }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                                            </label>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <h2 class="text-xl font-black text-[#071a80]">No matches yet</h2>
                    <p class="mt-2 text-blue-950/60">Generate a draw to create tournament matches.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $matches->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
