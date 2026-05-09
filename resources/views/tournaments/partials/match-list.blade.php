<div class="grid gap-4">
    @forelse ($matches as $match)
        @php
            $sideA = $match->players->where('side', 'A')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
            $sideB = $match->players->where('side', 'B')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
            $games = collect($match->score ?? [])->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))->values();
        @endphp
        <article class="rounded-md border border-blue-950/10 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-black uppercase tracking-[.18em] text-[#d6a31d]">{{ $match->draw_group ?: $match->tournamentCategory?->name }} | {{ str_replace('_', ' ', $match->status) }}</p>
                <p class="text-xs font-black uppercase text-blue-950/45">{{ $match->scheduled_at?->format('M j, g:i A') ?? $match->played_at?->format('M j, Y') }}{{ $match->court_label ? ' | '.$match->court_label : '' }}</p>
            </div>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <p class="rounded-md border border-blue-950/10 p-3 font-bold text-[#071a80] {{ $match->status === 'confirmed' && $match->winner_side === 'A' ? 'bg-blue-50' : '' }}">A: {{ $sideA }}</p>
                <p class="rounded-md border border-blue-950/10 p-3 font-bold text-[#071a80] {{ $match->status === 'confirmed' && $match->winner_side === 'B' ? 'bg-blue-50' : '' }}">B: {{ $sideB }}</p>
            </div>
            @if ($games->isNotEmpty())
                <p class="mt-3 text-sm font-black text-blue-950/60">{{ $games->map(fn ($game) => ((int) $game['a']).'-'.((int) $game['b']))->join(', ') }}</p>
            @endif
        </article>
    @empty
        <article class="rounded-md bg-white p-5 text-sm font-bold text-blue-950/60">No matches generated for this group yet.</article>
    @endforelse
</div>
