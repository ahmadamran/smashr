<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Draws | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($tournament->categories as $category)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ str_replace('_', ' ', $category->draw_mode) }}</p>
                            <h2 class="text-2xl font-black text-[#071a80]">{{ $category->name }}</h2>
                            <p class="mt-1 text-sm font-bold text-blue-950/50">{{ $category->entrants->where('status', 'approved')->count() }} approved entrants | {{ $category->matches->count() }} matches</p>
                        </div>
                        <form method="POST" action="{{ route('organizer.tournaments.draws.generate', [$tournament, $category]) }}">@csrf
                            <button class="rounded-md bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Generate draw</button>
                        </form>
                    </div>
                    <div class="mt-5 divide-y divide-blue-950/10">
                        @foreach ($category->entrants->where('status', 'approved')->sortBy('seed') as $entrant)
                            <p class="py-2 text-sm font-bold text-blue-950/70">{{ $entrant->seed ? '#'.$entrant->seed.' ' : '' }}{{ $entrant->displayName() }}</p>
                        @endforeach
                    </div>
                    <a href="{{ route('tournaments.draw', [$tournament, $category]) }}" class="mt-5 inline-flex rounded-md border border-blue-950/10 px-4 py-2 text-xs font-black uppercase text-[#071a80]">View public draw</a>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
