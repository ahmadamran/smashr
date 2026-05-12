<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Create tournament</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav')
        <form method="POST" action="{{ route('organizer.tournaments.store') }}" class="grid gap-4 rounded-lg bg-white p-6 shadow-lg md:grid-cols-2">@csrf
            @include('organizer.tournaments.partials.form', ['tournament' => null])
            <button class="rounded-md bg-brand-blue px-4 py-3 text-sm font-black uppercase text-white md:col-span-2">Create tournament</button>
        </form>
    </div>
</x-app-layout>
