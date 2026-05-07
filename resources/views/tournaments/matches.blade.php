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
                            <article class="rounded-md border border-blue-950/10 p-4">
                                <p class="text-xs font-black uppercase text-[#d6a31d]">{{ $match->tournamentCategory?->name ?? 'Tournament match' }} | {{ str_replace('_', ' ', $match->status) }}</p>
                                <p class="mt-2 font-bold text-[#071a80]">A: {{ $match->players->where('side', 'A')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA' }}</p>
                                <p class="mt-1 font-bold text-[#071a80]">B: {{ $match->players->where('side', 'B')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA' }}</p>
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
</x-app-layout>
