<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Registrations | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <section class="mb-8 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-2xl font-black text-[#071a80]">Add entrant</h2>
            <form method="POST" action="{{ route('organizer.tournaments.entrants.store', $tournament) }}" class="mt-5 grid gap-3 md:grid-cols-6">@csrf
                <select name="tournament_category_id" class="rounded-md border-gray-300 md:col-span-2">
                    @foreach ($tournament->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="player_one_id" class="rounded-md border-gray-300"><option value="">Player 1 user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->playerProfile?->display_name ?? $user->name }}</option>@endforeach</select>
                <select name="player_two_id" class="rounded-md border-gray-300"><option value="">Player 2 user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->playerProfile?->display_name ?? $user->name }}</option>@endforeach</select>
                <input name="player_one_name" placeholder="Player 1 name" class="rounded-md border-gray-300">
                <input name="player_two_name" placeholder="Player 2 name" class="rounded-md border-gray-300">
                <select name="status" class="rounded-md border-gray-300"><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option><option value="withdrawn">Withdrawn</option></select>
                <input name="seed" type="number" min="1" placeholder="Seed" class="rounded-md border-gray-300">
                <button class="rounded-md bg-[#071a80] px-4 py-2 text-sm font-black uppercase text-white md:col-span-4">Add entrant</button>
            </form>
        </section>

        <div class="grid gap-6">
            @foreach ($tournament->categories as $category)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-[#071a80]">{{ $category->name }}</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($category->entrants as $entrant)
                            <form method="POST" action="{{ route('organizer.tournaments.entrants.update', [$tournament, $entrant]) }}" class="grid items-center gap-3 rounded-md border border-blue-950/10 p-4 md:grid-cols-5">@csrf @method('PATCH')
                                <div class="md:col-span-2">
                                    <p class="font-black text-[#071a80]">{{ $entrant->displayName() ?: 'Unnamed entrant' }}</p>
                                    <p class="text-xs font-bold uppercase text-blue-950/40">{{ $entrant->created_at->format('M j, Y') }}</p>
                                </div>
                                <select name="status" class="rounded-md border-gray-300">
                                    @foreach (['pending','approved','rejected','withdrawn'] as $status)
                                        <option value="{{ $status }}" @selected($entrant->status === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <input name="seed" type="number" min="1" value="{{ $entrant->seed }}" placeholder="Seed" class="rounded-md border-gray-300">
                                <button class="rounded-md border border-blue-950/10 px-4 py-2 text-sm font-black uppercase text-[#071a80]">Save</button>
                            </form>
                        @empty
                            <p class="text-blue-950/60">No registrations yet.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
