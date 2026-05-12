<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Tournament detail</p><h2 class="mt-2 text-2xl font-black text-brand-blue">{{ $tournament->club?->name ?: 'No club' }}</h2></div>
                <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="rounded-md bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Edit</a>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-md bg-brand-surface p-4"><dt class="text-xs font-black uppercase text-brand-ink/50">Status</dt><dd><x-admin.status-badge :status="$tournament->status" /></dd></div>
                <div class="rounded-md bg-brand-surface p-4"><dt class="text-xs font-black uppercase text-brand-ink/50">Matches</dt><dd class="text-3xl font-black text-brand-blue">{{ $tournament->matches_count }}</dd></div>
                <div class="rounded-md bg-brand-surface p-4"><dt class="text-xs font-black uppercase text-brand-ink/50">Events</dt><dd class="text-3xl font-black text-brand-blue">{{ $tournament->categories->count() }}</dd></div>
            </dl>
        </section>
    </div>
</x-app-layout>
