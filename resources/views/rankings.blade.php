<x-app-layout>
    <x-slot name="header">
        @php
            $genderLabel = match ($gender) {
                'male' => "Men's",
                'female' => "Women's",
                default => 'Overall',
            };
        @endphp
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Smashr rankings</p>
            <h1 class="text-3xl font-black text-brand-blue">{{ $genderLabel }} {{ $format }} leaderboard</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-lg md:grid-cols-7">
            <input name="search" value="{{ request('search') }}" placeholder="Player name" class="rounded-md border-brand-ink/10 md:col-span-2">
            <select name="format" class="rounded-md border-brand-ink/10">
                <option value="singles" @selected($format === 'singles')>Singles</option>
                <option value="doubles" @selected($format === 'doubles')>Doubles</option>
            </select>
            <select name="gender" class="rounded-md border-brand-ink/10">
                <option value="">All genders</option>
                <option value="male" @selected($gender === 'male')>Men</option>
                <option value="female" @selected($gender === 'female')>Women</option>
            </select>
            <input name="country" value="{{ request('country') }}" placeholder="Country" class="rounded-md border-brand-ink/10">
            <input name="state" value="{{ request('state') }}" placeholder="State" class="rounded-md border-brand-ink/10">
            <input name="city" value="{{ request('city') }}" placeholder="City" class="rounded-md border-brand-ink/10">
            <button class="rounded-md bg-brand-blue px-4 py-2 text-sm font-black uppercase text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-lg bg-white shadow-lg">
            <table class="w-full text-left">
                <thead class="bg-brand-blue text-xs font-black uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-5 py-4">Rank</th>
                        <th class="px-5 py-4">Player</th>
                        <th class="px-5 py-4">Gender</th>
                        <th class="px-5 py-4">Location</th>
                        <th class="px-5 py-4">Matches</th>
                        <th class="px-5 py-4 text-right">Rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-ink/10">
                    @forelse ($players as $player)
                        <tr>
                            <td class="px-5 py-4 font-black">{{ $loop->iteration + ($players->currentPage() - 1) * $players->perPage() }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('players.show', $player) }}" class="font-black text-brand-blue hover:text-brand-green">{{ $player->display_name }}</a>
                                <p class="text-sm text-brand-ink/50">{{ $player->user->clubs->pluck('name')->join(', ') ?: 'Independent' }}</p>
                            </td>
                            <td class="px-5 py-4 text-brand-ink/70">{{ $player->gender ? ucfirst(str_replace('_', ' ', $player->gender)) : 'Not set' }}</td>
                            <td class="px-5 py-4 text-brand-ink/70">{{ collect([$player->city, $player->state, $player->country])->filter()->join(', ') ?: 'Not set' }}</td>
                            <td class="px-5 py-4">{{ $format === 'singles' ? $player->singles_matches : $player->doubles_matches }}</td>
                            <td class="px-5 py-4 text-right text-xl font-black">{{ $format === 'singles' ? $player->singles_rating : $player->doubles_rating }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-brand-ink/60">No confirmed {{ $genderLabel === 'Overall' ? '' : strtolower($genderLabel).' ' }}{{ $format }} matches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $players->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
