<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Tournament calendar</p>
                <h1 class="text-3xl font-black text-brand-blue sm:text-4xl">Badminton tournaments</h1>
            </div>
            <p class="max-w-xl text-sm font-bold text-brand-ink/60">Track published, draft, and archived events across SmashR clubs.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($tournaments as $tournament)
                <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-lg bg-white p-6 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">{{ $tournament->status }}</p>
                        <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ $tournament->starts_at?->format('M j') ?? 'TBA' }}</span>
                    </div>
                    <h2 class="mt-4 text-2xl font-black text-brand-blue">{{ $tournament->name }}</h2>
                    <p class="mt-3 text-sm text-brand-ink/60">{{ collect([$tournament->city, $tournament->state, $tournament->country])->filter()->join(', ') ?: 'Location TBA' }}</p>
                    <div class="mt-6 border-t border-brand-ink/10 pt-4">
                        <p class="text-sm font-bold text-brand-ink/60">{{ $tournament->club?->name ?? 'Independent tournament' }}</p>
                        @if ($tournament->starts_at && $tournament->ends_at)
                            <p class="mt-1 text-sm text-brand-ink/50">{{ $tournament->starts_at->format('M j, Y') }} - {{ $tournament->ends_at->format('M j, Y') }}</p>
                        @endif
                        <p class="mt-3 text-sm font-black uppercase text-brand-blue">{{ $tournament->matches_count }} matches</p>
                        <p class="mt-1 text-sm font-bold text-brand-ink/50">{{ $tournament->entrants_count }} entrants | {{ $tournament->categories_count }} categories</p>
                    </div>
                </a>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg md:col-span-2 lg:col-span-3">
                    <h2 class="text-2xl font-black text-brand-blue">No tournaments yet</h2>
                    <p class="mt-2 text-brand-ink/60">Publish tournaments from the admin area to show them here.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $tournaments->links('pagination.smashr') }}
        </div>
    </div>
</x-app-layout>
