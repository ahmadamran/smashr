<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Smashr rankings</p>
            <h1 class="text-3xl font-black text-[#071a80]">{{ ucfirst($format) }} leaderboard</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-lg md:grid-cols-5">
            <select name="format" class="rounded-md border-blue-950/10">
                <option value="singles" @selected($format === 'singles')>Singles</option>
                <option value="doubles" @selected($format === 'doubles')>Doubles</option>
            </select>
            <input name="country" value="{{ request('country') }}" placeholder="Country" class="rounded-md border-blue-950/10">
            <input name="state" value="{{ request('state') }}" placeholder="State" class="rounded-md border-blue-950/10">
            <input name="city" value="{{ request('city') }}" placeholder="City" class="rounded-md border-blue-950/10">
            <button class="rounded-md bg-[#071a80] px-4 py-2 text-sm font-black uppercase text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-lg bg-white shadow-lg">
            <table class="w-full text-left">
                <thead class="bg-[#071a80] text-xs font-black uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-5 py-4">Rank</th>
                        <th class="px-5 py-4">Player</th>
                        <th class="px-5 py-4">Location</th>
                        <th class="px-5 py-4">Matches</th>
                        <th class="px-5 py-4 text-right">Rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-950/10">
                    @forelse ($players as $player)
                        <tr>
                            <td class="px-5 py-4 font-black">{{ $loop->iteration + ($players->currentPage() - 1) * $players->perPage() }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('players.show', $player) }}" class="font-black text-[#071a80] hover:text-[#d6a31d]">{{ $player->display_name }}</a>
                                <p class="text-sm text-blue-950/50">{{ $player->user->clubs->first()?->name ?? 'Independent' }}</p>
                            </td>
                            <td class="px-5 py-4 text-blue-950/70">{{ collect([$player->city, $player->state, $player->country])->filter()->join(', ') ?: 'Not set' }}</td>
                            <td class="px-5 py-4">{{ $format === 'singles' ? $player->singles_matches : $player->doubles_matches }}</td>
                            <td class="px-5 py-4 text-right text-xl font-black">{{ $format === 'singles' ? $player->singles_rating : $player->doubles_rating }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-blue-950/60">No confirmed {{ $format }} matches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $players->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
