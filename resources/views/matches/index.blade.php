<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Competition feed</p>
                <h1 class="text-3xl font-black text-brand-blue sm:text-4xl">Submitted matches</h1>
            </div>
            <p class="max-w-xl text-sm font-bold text-brand-ink/60">Browse matches with submitted score data. Generated tournament draw placeholders stay hidden until a result is submitted.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form class="mb-6 grid gap-2 rounded-lg bg-white p-4 shadow-lg lg:grid-cols-[minmax(14rem,1.6fr)_minmax(8rem,.75fr)_minmax(10rem,.85fr)_minmax(9rem,.8fr)_minmax(12rem,1fr)_auto_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Search player, club, or tournament" class="rounded-md border-brand-ink/10 text-sm">
            <select name="format" class="rounded-md border-brand-ink/10 text-sm">
                <option value="">All formats</option>
                <option value="singles" @selected(request('format') === 'singles')>Singles</option>
                <option value="doubles" @selected(request('format') === 'doubles')>Doubles</option>
                <option value="mixed" @selected(request('format') === 'mixed')>Mixed</option>
            </select>
            <select name="status" class="rounded-md border-brand-ink/10 text-sm">
                <option value="">All statuses</option>
                @foreach (['pending_confirmation', 'confirmed', 'disputed', 'void'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
            <select name="club" class="rounded-md border-brand-ink/10 text-sm">
                <option value="">All clubs</option>
                @foreach ($clubs as $club)
                    <option value="{{ $club->slug }}" @selected(request('club') === $club->slug)>{{ $club->name }}</option>
                @endforeach
            </select>
            <select name="tournament" class="rounded-md border-brand-ink/10 text-sm">
                <option value="">All tournaments</option>
                @foreach ($tournaments as $tournament)
                    <option value="{{ $tournament->slug }}" @selected(request('tournament') === $tournament->slug)>{{ $tournament->name }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-brand-blue px-4 py-2 text-sm font-black uppercase text-white">Filter</button>
            @if (request()->hasAny(['search', 'format', 'status', 'club', 'tournament']))
                <a href="{{ route('matches.index') }}" class="rounded-md border border-brand-ink/10 px-4 py-2 text-center text-sm font-black uppercase text-brand-blue">Clear</a>
            @endif
        </form>

        <div class="grid gap-5 lg:grid-cols-2">
            @forelse ($matches as $match)
                @php
                    $sideAPlayers = $match->players->where('side', 'A')->sortBy('position')->values();
                    $sideBPlayers = $match->players->where('side', 'B')->sortBy('position')->values();
                    $games = collect($match->score ?? [])
                        ->filter(fn ($game) => array_key_exists('a', $game) && array_key_exists('b', $game))
                        ->values();
                    $clubNames = $match->club
                        ? collect([$match->club->name])
                        : $match->players
                            ->map(fn ($player) => $player->club?->name)
                            ->filter()
                            ->unique()
                            ->values();
                @endphp
                <article class="rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">{{ $match->format }} | {{ str_replace('_', ' ', $match->status) }}</p>
                        <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ $match->played_at->format('M j, Y') }}</span>
                    </div>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-md border border-brand-ink/10 p-4 {{ $match->winner_side === 'A' ? 'bg-brand-mist' : '' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-brand-blue">Side A</p>
                                @if ($match->winner_side === 'A')
                                    <span class="text-xs font-black uppercase text-brand-green">Winner</span>
                                @endif
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-1 gap-y-1 text-sm font-bold text-brand-ink/70">
                                @forelse ($sideAPlayers as $player)
                                    @php
                                        $profile = $player->user->playerProfile;
                                        $name = $profile?->display_name ?? $player->user->name;
                                    @endphp
                                    @if (! $loop->first)
                                        <span class="text-brand-ink/35">/</span>
                                    @endif
                                    @if ($profile)
                                        <a href="{{ route('players.show', $profile) }}" class="text-brand-blue hover:text-brand-green hover:underline" wire:navigate>{{ $name }}</a>
                                    @else
                                        <span>{{ $name }}</span>
                                    @endif
                                @empty
                                    <span>TBA</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="rounded-md bg-brand-blue p-4 text-white">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-mist">Match points</p>
                            @if ($games->isNotEmpty())
                                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                    @foreach ($games as $index => $game)
                                        <div class="rounded-md bg-white/10 px-3 py-2">
                                            <p class="text-[11px] font-black uppercase text-white/60">Game {{ $index + 1 }}</p>
                                            <p class="mt-1 text-2xl font-black">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-2 text-sm font-bold text-white/70">Match points not submitted yet</p>
                            @endif
                        </div>
                        <div class="rounded-md border border-brand-ink/10 p-4 {{ $match->winner_side === 'B' ? 'bg-brand-mist' : '' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-brand-blue">Side B</p>
                                @if ($match->winner_side === 'B')
                                    <span class="text-xs font-black uppercase text-brand-green">Winner</span>
                                @endif
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-1 gap-y-1 text-sm font-bold text-brand-ink/70">
                                @forelse ($sideBPlayers as $player)
                                    @php
                                        $profile = $player->user->playerProfile;
                                        $name = $profile?->display_name ?? $player->user->name;
                                    @endphp
                                    @if (! $loop->first)
                                        <span class="text-brand-ink/35">/</span>
                                    @endif
                                    @if ($profile)
                                        <a href="{{ route('players.show', $profile) }}" class="text-brand-blue hover:text-brand-green hover:underline" wire:navigate>{{ $name }}</a>
                                    @else
                                        <span>{{ $name }}</span>
                                    @endif
                                @empty
                                    <span>TBA</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-2 border-t border-brand-ink/10 pt-4 text-sm text-brand-ink/60">
                        <p><span class="font-black text-brand-blue">Club:</span> {{ $clubNames->join(', ') ?: 'None' }}</p>
                        <p><span class="font-black text-brand-blue">Tournament:</span> {{ $match->tournament?->name ?? 'None' }}</p>
                    </div>
                </article>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg lg:col-span-2">
                    <h2 class="text-2xl font-black text-brand-blue">No matches found</h2>
                    <p class="mt-2 text-brand-ink/60">Try clearing the filters or submit a new match.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $matches->links('pagination.smashr') }}
        </div>
    </div>
</x-app-layout>
