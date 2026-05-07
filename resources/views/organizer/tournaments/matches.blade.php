<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Matches | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        <div class="grid gap-4">
            @forelse ($matches as $match)
                <article class="rounded-lg bg-white p-5 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ $match->tournamentCategory?->name ?? 'Tournament match' }} | {{ str_replace('_', ' ', $match->status) }}</p>
                            <h2 class="mt-2 text-xl font-black text-[#071a80]">{{ $match->played_at->format('M j, Y') }}</h2>
                        </div>
                        <span class="rounded-full bg-[#f3f6fb] px-3 py-1 text-xs font-black uppercase text-[#071a80]">{{ $match->draw_group ?: 'Round '.$match->draw_round }}</span>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <p class="rounded-md border border-blue-950/10 p-3 font-bold text-blue-950/70">A: {{ $match->players->where('side', 'A')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA' }}</p>
                        <p class="rounded-md border border-blue-950/10 p-3 font-bold text-blue-950/70">B: {{ $match->players->where('side', 'B')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA' }}</p>
                    </div>
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
