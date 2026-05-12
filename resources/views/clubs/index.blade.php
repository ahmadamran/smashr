<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Club directory</p>
                <h1 class="text-3xl font-black text-brand-blue sm:text-4xl">Badminton clubs</h1>
            </div>
            <p class="max-w-xl text-sm font-bold text-brand-ink/60">Find SmashR clubs, view their members, and follow club leaderboard activity.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($clubs as $club)
                <a href="{{ route('clubs.show', $club) }}" class="rounded-lg bg-white p-6 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">
                    <p class="text-xs font-black uppercase tracking-[.22em] text-brand-green">{{ $club->city ?: $club->state ?: $club->country ?: 'Club' }}</p>
                    <h2 class="mt-3 text-2xl font-black text-brand-blue">{{ $club->name }}</h2>
                    <p class="mt-3 line-clamp-3 text-sm text-brand-ink/60">{{ $club->description ?: 'A SmashR badminton club.' }}</p>
                    <div class="mt-6 flex items-center justify-between border-t border-brand-ink/10 pt-4">
                        <span class="text-sm font-bold text-brand-ink/60">Members</span>
                        <span class="text-2xl font-black text-brand-blue">{{ $club->members_count }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg md:col-span-2 lg:col-span-3">
                    <h2 class="text-2xl font-black text-brand-blue">No clubs yet</h2>
                    <p class="mt-2 text-brand-ink/60">Create a profile and add your club to start the directory.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $clubs->links('pagination.smashr') }}
        </div>
    </div>
</x-app-layout>
