<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Superadmin</p>
        <h1 class="text-3xl font-black text-[#071a80]">Control centre</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="grid gap-5 md:grid-cols-5">
            @foreach ([
                'Users' => $usersCount,
                'Clubs' => $clubsCount,
                'Tournaments' => $tournamentsCount,
                'Pending / disputed' => $pendingMatchesCount,
                'Algorithm' => $activeAlgorithm->version,
            ] as $label => $value)
                <div class="rounded-lg bg-white p-6 shadow-lg">
                    <p class="text-xs font-black uppercase tracking-wide text-[#d6a31d]">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-black text-[#071a80]">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
