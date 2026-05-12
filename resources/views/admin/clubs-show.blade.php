<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $club->name }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Club detail</p>
                    <h2 class="mt-2 text-2xl font-black text-brand-blue">{{ $club->country }} {{ $club->state }} {{ $club->city }}</h2>
                </div>
                <a href="{{ route('admin.clubs.edit', $club) }}" class="rounded-md bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Edit club</a>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-md bg-brand-surface p-4"><p class="text-xs font-black uppercase text-brand-ink/50">Members</p><p class="text-3xl font-black text-brand-blue">{{ $club->members_count }}</p></div>
                <div class="rounded-md bg-brand-surface p-4"><p class="text-xs font-black uppercase text-brand-ink/50">Tournaments</p><p class="text-3xl font-black text-brand-blue">{{ $club->tournaments_count }}</p></div>
            </div>
            <form method="POST" action="{{ route('admin.clubs.members.store', $club) }}" class="mt-6 flex flex-col gap-3 sm:flex-row">@csrf
                <input name="email" placeholder="member@email.com" class="rounded-md border-brand-ink/10">
                <button class="rounded-md border border-brand-ink/10 px-4 py-2 text-xs font-black uppercase text-brand-blue">Link member</button>
            </form>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($club->members as $member)
                    <form method="POST" action="{{ route('admin.clubs.members.destroy', [$club, $member]) }}">@csrf @method('DELETE')
                        <button class="rounded-full bg-brand-surface px-3 py-1 text-xs font-bold">{{ $member->email }} x</button>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
