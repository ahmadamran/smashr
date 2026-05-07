<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Manage matches</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <form class="mb-6 flex gap-3">
            <select name="status" class="rounded-md border-gray-300">
                <option value="">All statuses</option>
                @foreach (['pending_confirmation', 'confirmed', 'disputed', 'void'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-[#071a80] px-4 font-bold text-white">Filter</button>
        </form>
        <div class="grid gap-5">
            @foreach ($matches as $match)
                <section class="rounded-lg bg-white p-5 shadow">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-[#d6a31d]">{{ $match->format }} | {{ str_replace('_', ' ', $match->status) }}</p>
                            <h2 class="text-xl font-black text-[#071a80]">Match #{{ $match->id }} | Side {{ $match->winner_side }} win</h2>
                            <p class="text-sm text-blue-950/60">{{ $match->players->map(fn ($p) => ($p->user->playerProfile?->display_name ?? $p->user->name).' ('.$p->side.')')->join(' vs ') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.matches.confirm', $match) }}">@csrf @method('PATCH')
                                <button class="rounded-md bg-green-600 px-3 py-2 text-xs font-bold text-white">Confirm</button>
                            </form>
                            <form method="POST" action="{{ route('admin.matches.void', $match) }}">@csrf @method('PATCH')
                                <button class="rounded-md bg-red-600 px-3 py-2 text-xs font-bold text-white">Void</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.matches.update', $match) }}" class="grid gap-3 md:grid-cols-5">@csrf @method('PATCH')
                        <select name="club_id" class="rounded-md border-gray-300"><option value="">No club</option>@foreach($clubs as $club)<option value="{{ $club->id }}" @selected($match->club_id === $club->id)>{{ $club->name }}</option>@endforeach</select>
                        <select name="tournament_id" class="rounded-md border-gray-300"><option value="">No tournament</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}" @selected($match->tournament_id === $tournament->id)>{{ $tournament->name }}</option>@endforeach</select>
                        <input name="played_at" type="date" value="{{ $match->played_at->toDateString() }}" class="rounded-md border-gray-300">
                        <select name="winner_side" class="rounded-md border-gray-300"><option value="A" @selected($match->winner_side === 'A')>Side A</option><option value="B" @selected($match->winner_side === 'B')>Side B</option></select>
                        <select name="status" class="rounded-md border-gray-300">@foreach(['pending_confirmation','confirmed','disputed','void'] as $status)<option value="{{ $status }}" @selected($match->status === $status)>{{ $status }}</option>@endforeach</select>
                        <button class="rounded-md border px-4 py-2 font-bold md:col-span-5">Save metadata</button>
                    </form>
                </section>
            @endforeach
        </div>
        <div class="mt-6">{{ $matches->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
