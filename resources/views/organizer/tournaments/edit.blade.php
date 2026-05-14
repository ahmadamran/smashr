<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <form method="POST" action="{{ route('organizer.tournaments.update', $tournament) }}" class="mb-8 grid gap-4 rounded-lg bg-white p-6 shadow-lg md:grid-cols-2">@csrf @method('PATCH')
            @include('organizer.tournaments.partials.form', ['tournament' => $tournament])
            <button class="rounded-md bg-brand-blue px-4 py-3 text-sm font-black uppercase text-white md:col-span-2">Save settings</button>
        </form>
    </div>
</x-app-layout>
