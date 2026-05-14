<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Tournament setup</p>
                    <h2 class="text-2xl font-black text-brand-blue">Categories</h2>
                </div>
                <a href="{{ route('organizer.tournaments.categories.create', $tournament) }}" class="w-fit rounded-full bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white shadow-sm transition hover:bg-brand-blue-dark">Create category</a>
            </div>
            <p class="mt-3 max-w-2xl text-sm font-bold text-brand-ink/60">Events players can register for. Draws and schedules use these categories to generate matches.</p>
        </section>

        <section class="mt-8 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-2xl font-black text-brand-blue">Category list</h2>
            <div class="mt-6 grid gap-3 md:grid-cols-2">
                @forelse ($tournament->categories as $category)
                    <div class="rounded-md border border-brand-ink/10 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-black text-brand-blue">{{ $category->name }}</h3>
                                <p class="mt-1 text-sm text-brand-ink/60">{{ ucfirst($category->format) }} | {{ str_replace('_', ' ', ucfirst($category->draw_mode)) }}{{ $category->draw_mode === 'round_robin' ? ' | groups of '.$category->group_size : '' }}</p>
                            </div>
                            <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ $category->status }}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 border-t border-brand-ink/10 pt-4 text-sm font-bold text-brand-ink/60">
                            <p>Level: <span class="text-brand-ink">{{ $category->level_label ?: 'Open' }}</span></p>
                            <p>Max: <span class="text-brand-ink">{{ $category->max_entrants ?: 'No limit' }}</span></p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-brand-ink/20 p-6 md:col-span-2">
                        <p class="font-bold text-brand-ink/60">No categories yet. Create the first category to open registration and draw setup.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
