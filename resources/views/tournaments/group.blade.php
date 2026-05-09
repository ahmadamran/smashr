<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-[#071a80]">{{ $category->name }} | {{ $groupName }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('tournaments.partials.nav', ['tournament' => $tournament, 'category' => $category])
        @include('tournaments.partials.group-tabs', ['tournament' => $tournament, 'category' => $category, 'groupName' => $groupName])

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('tournaments.draw.group', [$tournament, $category, str($groupName)->slug()]) }}" class="rounded-full bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Standings</a>
            <a href="{{ route('tournaments.draw.group.matches', [$tournament, $category, str($groupName)->slug()]) }}" class="rounded-full border border-blue-950/10 bg-white px-4 py-2 text-xs font-black uppercase text-[#071a80]">Matches</a>
        </div>

        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div class="mb-5">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Group standings</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">{{ $groupName }}</h2>
            </div>
            @include('tournaments.partials.standings-table', ['standings' => $standings])
        </section>
    </div>
</x-app-layout>
