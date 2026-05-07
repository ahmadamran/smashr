<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Competition feed</p>
                <h1 class="text-3xl font-black text-[#071a80] sm:text-4xl">All matches</h1>
            </div>
            <p class="max-w-xl text-sm font-bold text-blue-950/60">Browse confirmed, pending, disputed, and tournament-linked match activity across SmashR.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-lg md:grid-cols-5">
            <select name="format" class="rounded-md border-blue-950/10">
                <option value="">All formats</option>
                <option value="singles" @selected(request('format') === 'singles')>Singles</option>
                <option value="doubles" @selected(request('format') === 'doubles')>Doubles</option>
            </select>
            <select name="status" class="rounded-md border-blue-950/10">
                <option value="">All statuses</option>
                @foreach (['pending_confirmation', 'confirmed', 'disputed', 'void'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
            <select name="club" class="rounded-md border-blue-950/10">
                <option value="">All clubs</option>
                @foreach ($clubs as $club)
                    <option value="{{ $club->slug }}" @selected(request('club') === $club->slug)>{{ $club->name }}</option>
                @endforeach
            </select>
            <select name="tournament" class="rounded-md border-blue-950/10">
                <option value="">All tournaments</option>
                @foreach ($tournaments as $tournament)
                    <option value="{{ $tournament->slug }}" @selected(request('tournament') === $tournament->slug)>{{ $tournament->name }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-[#071a80] px-4 py-2 text-sm font-black uppercase text-white">Filter</button>
        </form>

        <div class="grid gap-5 lg:grid-cols-2">
            @forelse ($matches as $match)
                @php
                    $sideA = $match->players->where('side', 'A')->sortBy('position')->map(fn ($player) => $player->user->playerProfile?->display_name ?? $player->user->name)->join(' / ');
                    $sideB = $match->players->where('side', 'B')->sortBy('position')->map(fn ($player) => $player->user->playerProfile?->display_name ?? $player->user->name)->join(' / ');
                    $score = collect($match->score ?? [])->map(fn ($game) => ($game['a'] ?? 0).'-'.($game['b'] ?? 0))->join(', ');
                @endphp
                <article class="rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ $match->format }} | {{ str_replace('_', ' ', $match->status) }}</p>
                        <span class="rounded-full bg-[#f3f6fb] px-3 py-1 text-xs font-black uppercase text-[#071a80]">{{ $match->played_at->format('M j, Y') }}</span>
                    </div>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-md border border-blue-950/10 p-4 {{ $match->winner_side === 'A' ? 'bg-blue-50' : '' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-[#071a80]">Side A</p>
                                @if ($match->winner_side === 'A')
                                    <span class="text-xs font-black uppercase text-[#d6a31d]">Winner</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-bold text-blue-950/70">{{ $sideA }}</p>
                        </div>
                        <div class="rounded-md border border-blue-950/10 p-4 {{ $match->winner_side === 'B' ? 'bg-blue-50' : '' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-[#071a80]">Side B</p>
                                @if ($match->winner_side === 'B')
                                    <span class="text-xs font-black uppercase text-[#d6a31d]">Winner</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-bold text-blue-950/70">{{ $sideB }}</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-2 border-t border-blue-950/10 pt-4 text-sm text-blue-950/60 sm:grid-cols-3">
                        <p><span class="font-black text-[#071a80]">Score:</span> {{ $score }}</p>
                        <p><span class="font-black text-[#071a80]">Club:</span> {{ $match->club?->name ?? 'None' }}</p>
                        <p><span class="font-black text-[#071a80]">Tournament:</span> {{ $match->tournament?->name ?? 'None' }}</p>
                    </div>
                </article>
            @empty
                <div class="rounded-lg bg-white p-8 shadow-lg lg:col-span-2">
                    <h2 class="text-2xl font-black text-[#071a80]">No matches found</h2>
                    <p class="mt-2 text-blue-950/60">Try clearing the filters or submit a new match.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $matches->links('pagination.smashr') }}
        </div>
    </div>
</x-app-layout>
