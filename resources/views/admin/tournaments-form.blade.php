<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">{{ $tournament ? 'Edit tournament' : 'Create tournament' }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout :title="$tournament ? 'Edit tournament' : 'Create tournament'">
            <form method="POST" action="{{ $tournament ? route('admin.tournaments.update', $tournament) : route('admin.tournaments.store') }}" class="grid gap-4 md:grid-cols-3">
                @csrf
                @if ($tournament) @method('PATCH') @endif
                <label class="font-bold text-[#071a80] md:col-span-2">Name<input name="name" value="{{ old('name', $tournament?->name) }}" class="mt-1 w-full rounded-md border-blue-950/10"></label>
                <label class="font-bold text-[#071a80]">Club<select name="club_id" class="mt-1 w-full rounded-md border-blue-950/10"><option value="">No club</option>@foreach($clubs as $club)<option value="{{ $club->id }}" @selected(old('club_id', $tournament?->club_id) == $club->id)>{{ $club->name }}</option>@endforeach</select></label>
                @foreach (['country' => 'Country', 'state' => 'State', 'city' => 'City', 'venue' => 'Venue'] as $field => $label)
                    <label class="font-bold text-[#071a80]">{{ $label }}<input name="{{ $field }}" value="{{ old($field, $tournament?->{$field}) }}" class="mt-1 w-full rounded-md border-blue-950/10"></label>
                @endforeach
                <label class="font-bold text-[#071a80]">Start date<input name="starts_at" type="date" value="{{ old('starts_at', $tournament?->starts_at?->toDateString()) }}" class="mt-1 w-full rounded-md border-blue-950/10"></label>
                <label class="font-bold text-[#071a80]">End date<input name="ends_at" type="date" value="{{ old('ends_at', $tournament?->ends_at?->toDateString()) }}" class="mt-1 w-full rounded-md border-blue-950/10"></label>
                <label class="font-bold text-[#071a80]">Status<select name="status" class="mt-1 w-full rounded-md border-blue-950/10">@foreach(['draft','published','archived'] as $status)<option value="{{ $status }}" @selected(old('status', $tournament?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                <div class="flex gap-3 md:col-span-3">
                    <button class="rounded-md bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">Save tournament</button>
                    <a href="{{ route('admin.tournaments') }}" class="rounded-md border border-blue-950/10 px-5 py-3 text-xs font-black uppercase text-[#071a80]">Cancel</a>
                </div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
