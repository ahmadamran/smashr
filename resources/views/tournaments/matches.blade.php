@php
    $hasLiveMatches = $matches->flatten(1)->contains(fn ($match) => $match->live_status === 'live');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-[#071a80]">Tournament matches</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form class="mb-6 flex flex-col gap-3 rounded-lg bg-white p-5 shadow-lg sm:flex-row">
            <input name="date" type="date" value="{{ request('date') }}" class="rounded-md border-blue-950/10">
            <button class="rounded-md bg-[#071a80] px-4 py-2 text-sm font-black uppercase text-white">Filter date</button>
            <a href="{{ route('tournaments.matches', $tournament) }}" class="rounded-md border border-blue-950/10 px-4 py-2 text-center text-sm font-black uppercase text-[#071a80]">Clear</a>
        </form>

        <div class="grid gap-6">
            @forelse ($matches as $date => $dayMatches)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-[#071a80]">{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y') }}</h2>
                    <div class="mt-5 grid gap-4">
                        @foreach ($dayMatches as $match)
                            @php
                                $sideA = $match->players->where('side', 'A')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
                                $sideB = $match->players->where('side', 'B')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
                                $officialGames = collect($match->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
                                $liveScore = $match->live_score ?? [];
                                $liveGames = collect($liveScore['games'] ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
                                $current = $liveScore['current'] ?? ['a' => 0, 'b' => 0];
                                $displayGames = $officialGames->isNotEmpty() ? $officialGames : $liveGames;
                            @endphp
                            <article class="rounded-md border border-blue-950/10 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-black uppercase text-[#d6a31d]">{{ $match->tournamentCategory?->name ?? 'Tournament match' }} | {{ str_replace('_', ' ', $match->status) }}</p>
                                    @if ($match->live_status === 'live')
                                        <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">Live</span>
                                    @elseif ($match->live_status === 'submitted')
                                        <span class="rounded-full bg-[#d6a31d] px-3 py-1 text-xs font-black uppercase text-[#071a80]">Review</span>
                                    @elseif ($match->live_status === 'approved')
                                        <span class="rounded-full bg-green-700 px-3 py-1 text-xs font-black uppercase text-white">Approved</span>
                                    @endif
                                </div>
                                <p class="mt-3 font-bold text-[#071a80]">A: {{ $sideA }}</p>
                                <p class="mt-1 font-bold text-[#071a80]">B: {{ $sideB }}</p>

                                @if ($match->live_status === 'live')
                                    <div class="mt-4 rounded-md bg-[#071a80] p-4 text-white">
                                        <p class="text-xs font-black uppercase tracking-[.18em] text-[#d6a31d]">Current game {{ $liveScore['current_game'] ?? 1 }}</p>
                                        <p class="mt-2 text-3xl font-black">A {{ (int) ($current['a'] ?? 0) }} - {{ (int) ($current['b'] ?? 0) }} B</p>
                                    </div>
                                @elseif ($displayGames->isNotEmpty())
                                    <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                        @foreach ($displayGames as $index => $game)
                                            <div class="rounded-md bg-[#f3f6fb] px-3 py-2">
                                                <p class="text-[11px] font-black uppercase text-blue-950/45">Game {{ $index + 1 }}</p>
                                                <p class="mt-1 text-xl font-black text-[#071a80]">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-4 rounded-md bg-[#f3f6fb] p-3 text-sm font-bold text-blue-950/60">Match points not submitted yet.</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-xl font-black text-[#071a80]">No matches scheduled</h2>
                    <p class="mt-2 text-blue-950/60">Generated draw matches will appear here.</p>
                </section>
            @endforelse
        </div>
    </div>
    @if ($hasLiveMatches)
        <script>
            setTimeout(() => window.location.reload(), 10000);
        </script>
    @endif
</x-app-layout>
