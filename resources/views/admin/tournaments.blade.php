<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Manage tournaments</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <form method="POST" action="{{ route('admin.tournaments.store') }}" class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-4">@csrf
            <input name="name" placeholder="Tournament name" class="rounded-md border-gray-300">
            <select name="club_id" class="rounded-md border-gray-300"><option value="">No club</option>@foreach($clubs as $club)<option value="{{ $club->id }}">{{ $club->name }}</option>@endforeach</select>
            <input name="starts_at" type="date" class="rounded-md border-gray-300">
            <select name="status" class="rounded-md border-gray-300"><option>draft</option><option>published</option><option>archived</option></select>
            <button class="rounded-md bg-[#071a80] py-2 font-black uppercase text-white md:col-span-4">Create tournament</button>
        </form>
        <div class="grid gap-5">
            @foreach ($tournaments as $tournament)
                <form method="POST" action="{{ route('admin.tournaments.update', $tournament) }}" class="grid gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-6">@csrf @method('PATCH')
                    <input name="name" value="{{ $tournament->name }}" class="rounded-md border-gray-300 md:col-span-2">
                    <select name="club_id" class="rounded-md border-gray-300"><option value="">No club</option>@foreach($clubs as $club)<option value="{{ $club->id }}" @selected($tournament->club_id === $club->id)>{{ $club->name }}</option>@endforeach</select>
                    <input name="starts_at" type="date" value="{{ $tournament->starts_at?->toDateString() }}" class="rounded-md border-gray-300">
                    <input name="ends_at" type="date" value="{{ $tournament->ends_at?->toDateString() }}" class="rounded-md border-gray-300">
                    <select name="status" class="rounded-md border-gray-300"><option @selected($tournament->status === 'draft')>draft</option><option @selected($tournament->status === 'published')>published</option><option @selected($tournament->status === 'archived')>archived</option></select>
                    <input name="country" value="{{ $tournament->country }}" placeholder="Country" class="rounded-md border-gray-300">
                    <input name="state" value="{{ $tournament->state }}" placeholder="State" class="rounded-md border-gray-300">
                    <input name="city" value="{{ $tournament->city }}" placeholder="City" class="rounded-md border-gray-300">
                    <p class="py-2 font-bold">{{ $tournament->matches_count ?? $tournament->matches->count() }} matches</p>
                    <button class="rounded-md bg-[#071a80] font-bold text-white md:col-span-2">Save</button>
                </form>
            @endforeach
        </div>
        <div class="mt-6">{{ $tournaments->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
