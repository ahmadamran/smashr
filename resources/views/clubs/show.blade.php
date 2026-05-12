<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Club profile</p>
            <h1 class="text-4xl font-black text-brand-blue">{{ $club->name }}</h1>
            <p class="mt-2 text-brand-ink/60">{{ collect([$club->city, $club->state, $club->country])->filter()->join(', ') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
            <section class="rounded-lg bg-brand-blue p-8 text-white shadow-lg">
                <h2 class="text-2xl font-black">About</h2>
                <p class="mt-4 text-white/70">{{ $club->description ?: 'A Smashr badminton club.' }}</p>
                <p class="mt-8 text-5xl font-black">{{ $club->members->count() }}</p>
                <p class="text-white/70">members</p>
            </section>
            <section class="rounded-lg bg-white p-8 shadow-lg">
                <h2 class="text-2xl font-black text-brand-blue">Club leaderboard</h2>
                <div class="mt-6 divide-y divide-brand-ink/10">
                    @forelse ($club->members->sortByDesc(fn ($user) => $user->playerProfile?->doubles_rating ?? 0) as $member)
                        @if ($member->playerProfile)
                            <a href="{{ route('players.show', $member->playerProfile) }}" class="flex items-center justify-between py-4">
                                <span class="font-black text-brand-blue">{{ $member->playerProfile->display_name }}</span>
                                <span class="font-black">{{ $member->playerProfile->doubles_rating }}</span>
                            </a>
                        @endif
                    @empty
                        <p class="text-brand-ink/60">No players have joined this club yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
