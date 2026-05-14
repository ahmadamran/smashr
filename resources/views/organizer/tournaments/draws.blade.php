<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Draws | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="mb-6 rounded bg-red-50 p-4 font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Tournament control</p>
                    <h2 class="mt-1 text-2xl font-black text-brand-blue">Draws</h2>
                    <p class="mt-2 max-w-2xl text-sm font-bold text-brand-ink/60">Manage each event draw, preview schedules, and generate the match records shown on the public draw and matches pages.</p>
                </div>
                <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-full border border-brand-ink/10 px-4 py-2 text-xs font-black uppercase text-brand-blue">Public page</a>
            </div>
        </section>

        <div class="mt-6 grid gap-5 lg:grid-cols-2">
            @foreach ($tournament->categories as $category)
                @php
                    $approvedEntrants = $category->entrants->where('status', 'approved');
                    $matches = $category->matches;
                    $latestScheduled = $matches->pluck('scheduled_at')->filter()->sortDesc()->first();
                @endphp
                <article class="rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">{{ str_replace('_', ' ', $category->format) }} | {{ str_replace('_', ' ', $category->draw_mode) }}</p>
                            <h3 class="mt-1 text-2xl font-black text-brand-blue">{{ $category->name }}</h3>
                            <p class="mt-2 text-sm font-bold text-brand-ink/55">
                                {{ $approvedEntrants->count() }} approved entrants
                                <span class="mx-1 text-brand-ink/25">|</span>
                                {{ $matches->count() }} matches
                                @if ($category->draw_mode === 'round_robin')
                                    <span class="mx-1 text-brand-ink/25">|</span>
                                    Group of {{ $category->group_size ?? 4 }}
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full {{ $matches->isEmpty() ? 'bg-brand-surface text-brand-blue' : 'bg-brand-green text-white' }} px-3 py-1 text-xs font-black uppercase">
                            {{ $matches->isEmpty() ? 'Not generated' : 'Generated' }}
                        </span>
                    </div>

                    @if ($latestScheduled)
                        <p class="mt-4 text-sm font-bold text-brand-ink/60">Latest scheduled match: {{ $latestScheduled->format('M j, g:i A') }}</p>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('organizer.tournaments.draws.manage', [$tournament, $category]) }}" class="rounded-md bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Manage draw</a>
                        <a href="{{ route('tournaments.draw', [$tournament, $category]) }}" class="rounded-md border border-brand-ink/10 px-4 py-2 text-xs font-black uppercase text-brand-blue">View public draw</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
