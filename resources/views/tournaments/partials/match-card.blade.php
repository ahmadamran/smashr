@php
    $sideA = $match->players->where('side', 'A')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
    $sideB = $match->players->where('side', 'B')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
    $officialGames = collect($match->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
    $liveScore = $match->live_score ?? [];
    $liveGames = collect($liveScore['games'] ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
    $current = $liveScore['current'] ?? ['a' => 0, 'b' => 0];
    $displayGames = $officialGames->isNotEmpty() ? $officialGames : $liveGames;
    $showWinnerFlag = $match->status === 'confirmed' && $officialGames->isNotEmpty() && filled($match->winner_side);
@endphp

<article class="rounded-md border border-brand-ink/10 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-black uppercase text-brand-green">{{ $match->tournamentCategory?->name ?? 'Tournament match' }} | {{ str_replace('_', ' ', $match->status) }}</p>
        @if ($match->live_status === 'live')
            <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">Live</span>
        @elseif ($match->live_status === 'submitted')
            <span class="rounded-full bg-brand-green px-3 py-1 text-xs font-black uppercase text-white">Review</span>
        @elseif ($match->live_status === 'approved')
            <span class="rounded-full bg-green-700 px-3 py-1 text-xs font-black uppercase text-white">Approved</span>
        @endif
    </div>
    @if ($match->scheduled_at || $match->court_label)
        <div class="mt-3 flex flex-wrap gap-2 text-xs font-black uppercase text-brand-blue">
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
    <div class="mt-4 grid gap-2">
        @foreach (['A' => $sideA, 'B' => $sideB] as $side => $sideName)
            @php($isWinner = $showWinnerFlag && $match->winner_side === $side)
            <div class="flex items-center justify-between gap-3 rounded-md border px-3 py-3 {{ $isWinner ? 'border-brand-green/30 bg-brand-mist' : 'border-brand-ink/10 bg-white' }}">
                <p class="font-black {{ $isWinner ? 'text-brand-green' : 'text-brand-blue/80' }}">{{ $side }}: {{ $sideName }}</p>
                @if ($isWinner)
                    <span class="shrink-0 rounded-full bg-brand-green px-3 py-1 text-[11px] font-black uppercase text-white">Winner</span>
                @endif
            </div>
        @endforeach
    </div>

    @if ($match->live_status === 'live')
        <div class="mt-4 rounded-md bg-brand-blue p-4 text-white">
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-mist">Current game {{ $liveScore['current_game'] ?? 1 }}</p>
            <p class="mt-2 text-3xl font-black">A {{ (int) ($current['a'] ?? 0) }} - {{ (int) ($current['b'] ?? 0) }} B</p>
        </div>
        @if ($liveGames->isNotEmpty())
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                @foreach ($liveGames as $index => $game)
                    <div class="rounded-md bg-brand-surface px-3 py-2">
                        <p class="text-[11px] font-black uppercase text-brand-ink/45">Completed game {{ $index + 1 }}</p>
                        <p class="mt-1 text-xl font-black text-brand-blue">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    @elseif ($displayGames->isNotEmpty())
        <div class="mt-4 grid gap-2 sm:grid-cols-3">
            @foreach ($displayGames as $index => $game)
                <div class="rounded-md bg-brand-surface px-3 py-2">
                    <p class="text-[11px] font-black uppercase text-brand-ink/45">Game {{ $index + 1 }}</p>
                    <p class="mt-1 text-xl font-black text-brand-blue">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="mt-4 rounded-md bg-brand-surface p-3 text-sm font-bold text-brand-ink/60">Match points not submitted yet.</p>
    @endif
</article>
