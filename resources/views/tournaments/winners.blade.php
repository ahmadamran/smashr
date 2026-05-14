<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-brand-blue">Tournament winners</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('tournaments.partials.nav', ['tournament' => $tournament])

        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div>
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Tournament winners</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">Top 4 finishers</h2>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                @forelse ($winnerGroups as $group)
                    <article class="rounded-md border border-brand-ink/10 p-4">
                        <h3 class="text-sm font-black uppercase tracking-[.18em] text-brand-green">{{ $group['category']->name }}</h3>
                        <div class="mt-3 divide-y divide-brand-ink/10">
                            @foreach ($group['placements'] as $row)
                                @php
                                    $profiles = $row['entrant']->players
                                        ->sortBy('position')
                                        ->map(fn ($player) => $player->user?->playerProfile)
                                        ->filter()
                                        ->values();
                                    $clubNames = $row['entrant']->players
                                        ->sortBy('position')
                                        ->flatMap(fn ($player) => filled($player->school_name)
                                            ? [$player->school_name]
                                            : ($player->user?->clubs?->pluck('name')->all() ?? []))
                                        ->filter()
                                        ->unique()
                                        ->values();
                                @endphp
                                <div class="grid grid-cols-[2rem_1fr] gap-3 py-3">
                                    <p class="font-black text-brand-ink/70">{{ $row['rank'] }}</p>
                                    <div class="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-start">
                                        <div>
                                            @if ($profiles->count() === 1)
                                                <a href="{{ route('players.show', $profiles->first()) }}" class="font-black text-brand-blue hover:text-brand-green">{{ $row['entrant']->displayName() }}</a>
                                            @elseif ($profiles->count() > 1)
                                                <div class="flex flex-wrap gap-x-2 gap-y-1">
                                                    @foreach ($row['entrant']->players->sortBy('position') as $player)
                                                        @if ($player->user?->playerProfile)
                                                            <a href="{{ route('players.show', $player->user->playerProfile) }}" class="font-black text-brand-blue hover:text-brand-green">{{ $player->displayName() }}</a>
                                                        @else
                                                            <span class="font-black text-brand-blue">{{ $player->displayName() }}</span>
                                                        @endif
                                                        @if (! $loop->last)
                                                            <span class="font-black text-brand-ink/40">/</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="font-black text-brand-blue">{{ $row['entrant']->displayName() }}</p>
                                            @endif
                                            @if ($row['entrant']->seed)
                                                <p class="mt-1 text-xs font-black uppercase text-brand-ink/40">Seed {{ $row['entrant']->seed }}</p>
                                            @endif
                                        </div>
                                        @if ($clubNames->isNotEmpty())
                                            <p class="text-right text-xs font-black uppercase tracking-wide text-brand-ink/45">{{ $clubNames->join(', ') }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg bg-brand-surface p-8 text-center font-bold text-brand-ink/60 md:col-span-2">
                        No confirmed tournament winners yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
