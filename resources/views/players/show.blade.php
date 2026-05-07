<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Player profile</p>
                <h1 class="text-4xl font-black text-[#071a80]">{{ $player->display_name }}</h1>
            </div>
            <p class="font-bold text-blue-950/60">{{ collect([$player->city, $player->state, $player->country])->filter()->join(', ') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        <section class="rounded-lg bg-[#071a80] p-8 text-white shadow-lg lg:col-span-1">
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Primary format</p>
            <h2 class="mt-3 text-3xl font-black">{{ ucfirst($player->primary_format) }}</h2>
            <p class="mt-6 text-white/70">{{ ucfirst($player->preferred_hand) }} handed</p>
            <p class="text-white/70">{{ $player->user->clubs->first()?->name ?? 'Independent player' }}</p>
        </section>
        <section class="grid gap-6 md:grid-cols-2 lg:col-span-2">
            <div class="rounded-lg bg-white p-8 shadow-lg">
                <p class="text-xs font-black uppercase text-[#d6a31d]">Singles rating</p>
                <p class="mt-3 text-6xl font-black text-[#071a80]">{{ $player->singles_rating }}</p>
                <p class="mt-2 text-blue-950/60">{{ $player->singles_matches }} confirmed matches</p>
            </div>
            <div class="rounded-lg bg-white p-8 shadow-lg">
                <p class="text-xs font-black uppercase text-[#d6a31d]">Doubles rating</p>
                <p class="mt-3 text-6xl font-black text-[#071a80]">{{ $player->doubles_rating }}</p>
                <p class="mt-2 text-blue-950/60">{{ $player->doubles_matches }} confirmed matches</p>
            </div>
        </section>
    </div>
</x-app-layout>
