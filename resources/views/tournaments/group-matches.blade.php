<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-brand-blue">{{ $category->name }} | {{ $groupName }} matches</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('tournaments.partials.nav', ['tournament' => $tournament, 'category' => $category])
        @include('tournaments.partials.group-tabs', ['tournament' => $tournament, 'category' => $category, 'groupName' => $groupName])

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('tournaments.draw.group', [$tournament, $category, str($groupName)->slug()]) }}" class="rounded-full border border-brand-ink/10 bg-white px-4 py-2 text-xs font-black uppercase text-brand-blue">Standings</a>
            <a href="{{ route('tournaments.draw.group.matches', [$tournament, $category, str($groupName)->slug()]) }}" class="rounded-full bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Matches</a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[.75fr_1.25fr]">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Table</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">{{ $groupName }}</h2>
                <div class="mt-5">
                    @include('tournaments.partials.standings-table', ['standings' => $standings])
                </div>
            </section>
            <section class="rounded-lg bg-brand-surface p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Matches</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">Group fixtures</h2>
                <div class="mt-5">
                    @include('tournaments.partials.match-list', ['matches' => $matches])
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
