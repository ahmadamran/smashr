<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Match #{{ $match->id }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ $match->format }}</p><h2 class="mt-2 text-2xl font-black text-[#071a80]">{{ $match->players->map(fn ($p) => ($p->user->playerProfile?->display_name ?? $p->user->name).' ('.$p->side.')')->join(' vs ') }}</h2></div>
                <a href="{{ route('admin.matches.edit', $match) }}" class="rounded-md bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Edit</a>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-md bg-[#f3f6fb] p-4"><dt class="text-xs font-black uppercase text-blue-950/50">Status</dt><dd><x-admin.status-badge :status="$match->status" /></dd></div>
                <div class="rounded-md bg-[#f3f6fb] p-4"><dt class="text-xs font-black uppercase text-blue-950/50">Tournament</dt><dd class="font-bold">{{ $match->tournament?->name ?: '-' }}</dd></div>
                <div class="rounded-md bg-[#f3f6fb] p-4"><dt class="text-xs font-black uppercase text-blue-950/50">Court</dt><dd class="font-bold">{{ $match->court_label ?: '-' }}</dd></div>
            </dl>
        </section>
    </div>
</x-app-layout>
