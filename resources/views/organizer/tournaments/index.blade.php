<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Organizer tournaments</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav')
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($tournaments as $tournament)
                <a href="{{ route('organizer.tournaments.edit', $tournament) }}" class="rounded-lg bg-white p-6 shadow-lg transition hover:shadow-xl">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ $tournament->status }} | {{ $tournament->registration_status }}</p>
                    <h2 class="mt-3 text-2xl font-black text-[#071a80]">{{ $tournament->name }}</h2>
                    <p class="mt-2 text-sm text-blue-950/60">{{ $tournament->starts_at?->format('M j, Y') ?? 'Date TBA' }}</p>
                    <div class="mt-5 grid grid-cols-3 gap-2 border-t border-blue-950/10 pt-4 text-center">
                        <p class="text-sm font-black text-[#071a80]">{{ $tournament->categories_count }}<span class="block text-xs font-bold uppercase text-blue-950/40">Categories</span></p>
                        <p class="text-sm font-black text-[#071a80]">{{ $tournament->entrants_count }}<span class="block text-xs font-bold uppercase text-blue-950/40">Entrants</span></p>
                        <p class="text-sm font-black text-[#071a80]">{{ $tournament->matches_count }}<span class="block text-xs font-bold uppercase text-blue-950/40">Matches</span></p>
                    </div>
                </a>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg md:col-span-2 lg:col-span-3">
                    <h2 class="text-2xl font-black text-[#071a80]">No tournaments yet</h2>
                    <a href="{{ route('organizer.tournaments.create') }}" class="mt-4 inline-flex rounded-md bg-[#071a80] px-4 py-3 text-sm font-black uppercase text-white">Create tournament</a>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $tournaments->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
