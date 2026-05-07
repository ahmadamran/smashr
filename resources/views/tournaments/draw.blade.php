<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-[#071a80]">{{ $category->name }} draw</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap gap-3">
            <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-full bg-white px-4 py-2 text-sm font-black uppercase text-[#071a80]">Info</a>
            <a href="{{ route('tournaments.matches', $tournament) }}" class="rounded-full bg-white px-4 py-2 text-sm font-black uppercase text-[#071a80]">Matches</a>
        </div>

        @if ($category->draw_mode === 'round_robin')
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($category->entrants->where('status', 'approved')->groupBy('group_name') as $group => $entrants)
                    <section class="rounded-lg bg-white p-6 shadow-lg">
                        <h2 class="text-xl font-black text-[#071a80]">{{ $group ?: 'Ungrouped' }}</h2>
                        <div class="mt-4 divide-y divide-blue-950/10">
                            @foreach ($entrants as $entrant)
                                <p class="py-2 font-bold text-blue-950/70">{{ $entrant->draw_position }}. {{ $entrant->displayName() }}</p>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2">
                @forelse ($category->matches->sortBy('draw_position') as $match)
                    <section class="rounded-lg bg-white p-6 shadow-lg">
                        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Round {{ $match->draw_round }} | Match {{ $match->draw_position }}</p>
                        <div class="mt-4 grid gap-3">
                            <p class="rounded-md border border-blue-950/10 p-3 font-bold text-[#071a80]">A: {{ $match->players->where('side', 'A')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') }}</p>
                            <p class="rounded-md border border-blue-950/10 p-3 font-bold text-[#071a80]">B: {{ $match->players->where('side', 'B')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') }}</p>
                        </div>
                    </section>
                @empty
                    <section class="rounded-lg bg-white p-6 shadow-lg">
                        <h2 class="text-xl font-black text-[#071a80]">Draw not generated yet</h2>
                        <p class="mt-2 text-blue-950/60">Approved entrants will appear here after the organizer generates the draw.</p>
                    </section>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
