@php
    $hasLiveMatches = $liveMatches->isNotEmpty();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-[#071a80]">Tournament schedule</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('tournaments.partials.nav', ['tournament' => $tournament])
        <form class="mb-6 flex flex-col gap-3 rounded-lg bg-white p-5 shadow-lg sm:flex-row">
            <input name="date" type="date" value="{{ request('date') }}" class="rounded-md border-blue-950/10">
            <button class="rounded-md bg-[#071a80] px-4 py-2 text-sm font-black uppercase text-white">Filter date</button>
            <a href="{{ route('tournaments.matches', $tournament) }}" class="rounded-md border border-blue-950/10 px-4 py-2 text-center text-sm font-black uppercase text-[#071a80]">Clear</a>
        </form>

        <div class="grid gap-6">
            @if ($liveMatches->isNotEmpty())
                <section id="live" class="rounded-lg bg-white p-6 shadow-lg ring-2 ring-red-600/10">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-red-600">Live now</p>
                            <h2 class="text-2xl font-black text-[#071a80]">Live matches</h2>
                        </div>
                        <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">{{ $liveMatches->count() }} live</span>
                    </div>

                    <div class="mt-5 grid gap-4">
                        @foreach ($liveMatches as $match)
                            @include('tournaments.partials.match-card', ['match' => $match])
                        @endforeach
                    </div>
                </section>
            @endif

            @forelse ($matches as $date => $dayMatches)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-[#071a80]">{{ \Illuminate\Support\Carbon::parse($date)->format('M j, Y') }}</h2>
                    <div class="mt-5 grid gap-4">
                        @foreach ($dayMatches as $match)
                            @include('tournaments.partials.match-card', ['match' => $match])
                        @endforeach
                    </div>
                </section>
            @empty
                @if ($liveMatches->isEmpty())
                    <section class="rounded-lg bg-white p-6 shadow-lg">
                        <h2 class="text-xl font-black text-[#071a80]">No matches scheduled</h2>
                        <p class="mt-2 text-blue-950/60">Generated draw matches will appear here.</p>
                    </section>
                @endif
            @endforelse
        </div>
    </div>
    @if ($hasLiveMatches)
        <script>
            setTimeout(() => window.location.reload(), 10000);
        </script>
    @endif
</x-app-layout>
