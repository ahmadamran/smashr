<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Superadmin</p>
        <h1 class="text-3xl font-black text-[#071a80]">Control centre</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                'Total users' => $usersCount,
                'Active users' => $activeUsersCount,
                'Suspended users' => $suspendedUsersCount,
                'Clubs' => $clubsCount,
                'Tournaments' => $tournamentsCount,
                'Published tournaments' => $publishedTournamentsCount,
                'Draft tournaments' => $draftTournamentsCount,
                'Pending matches' => $pendingMatchesCount,
                'Disputed matches' => $disputedMatchesCount,
                'Active algorithm' => $activeAlgorithm->version,
                'Matches this month' => $matchesThisMonth,
                'New users this month' => $newUsersThisMonth,
            ] as $label => $value)
                <div class="rounded-lg bg-white p-5 shadow-lg">
                    <p class="text-xs font-black uppercase tracking-wide text-[#d6a31d]">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-black text-[#071a80]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <section class="mt-8 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-xl font-black text-[#071a80]">Quick actions</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('admin.users.create') }}" class="rounded-md bg-[#071a80] px-4 py-3 text-xs font-black uppercase text-white">Create user</a>
                <a href="{{ route('admin.clubs.create') }}" class="rounded-md bg-[#071a80] px-4 py-3 text-xs font-black uppercase text-white">Create club</a>
                <a href="{{ route('admin.tournaments.create') }}" class="rounded-md bg-[#071a80] px-4 py-3 text-xs font-black uppercase text-white">Create tournament</a>
                <a href="{{ route('admin.matches.create') }}" class="rounded-md border border-blue-950/10 px-4 py-3 text-xs font-black uppercase text-[#071a80]">Add match</a>
                <form method="POST" action="{{ route('admin.algorithms.recalculate.preview', $activeAlgorithm) }}">@csrf<button class="rounded-md border border-blue-950/10 px-4 py-3 text-xs font-black uppercase text-[#071a80]">Recalculate ratings</button></form>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-xl font-black text-[#071a80]">Recent activity</h2>
                <div class="mt-4 grid gap-3">
                    @foreach ($recentUsers as $user)
                        <p class="rounded-md bg-[#f3f6fb] p-3 text-sm font-bold">{{ $user->name }} joined {{ $user->created_at->diffForHumans() }}</p>
                    @endforeach
                </div>
            </section>
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-xl font-black text-[#071a80]">Recent tournaments</h2>
                <div class="mt-4 grid gap-3">
                    @foreach ($recentTournaments as $tournament)
                        <a href="{{ route('admin.tournaments.show', $tournament) }}" class="rounded-md bg-[#f3f6fb] p-3 text-sm font-bold hover:text-[#071a80]">{{ $tournament->name }} | {{ $tournament->status }}</a>
                    @endforeach
                </div>
            </section>
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-xl font-black text-[#071a80]">Pending admin tasks</h2>
                <div class="mt-4 grid gap-3">
                    <a href="{{ route('admin.matches', ['status' => 'pending_confirmation']) }}" class="rounded-md bg-[#f3f6fb] p-3 text-sm font-bold">{{ $pendingMatchesCount }} matches pending confirmation</a>
                    <a href="{{ route('admin.matches', ['status' => 'disputed']) }}" class="rounded-md bg-[#f3f6fb] p-3 text-sm font-bold">{{ $disputedMatchesCount }} disputes need review</a>
                </div>
            </section>
        </div>

        <section class="mt-8 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-xl font-black text-[#071a80]">Recent disputes</h2>
            <div class="mt-4 grid gap-3">
                @forelse ($recentDisputes as $match)
                    <a href="{{ route('admin.matches.show', $match) }}" class="rounded-md bg-[#f3f6fb] p-3 text-sm font-bold">Match #{{ $match->id }} | {{ $match->tournament?->name ?: 'Independent match' }}</a>
                @empty
                    <p class="text-sm font-bold text-blue-950/50">No recent disputes.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
