<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Tournament</p>
                <h1 class="text-3xl font-black text-brand-blue sm:text-4xl">{{ $tournament->name }}</h1>
            </div>
            @auth
                @if ($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'))
                    <a href="{{ route('organizer.tournaments.edit', $tournament) }}" class="w-fit rounded-full bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Manage tournament</a>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif
        @include('tournaments.partials.nav', ['tournament' => $tournament])

        <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Info</p>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-black uppercase text-brand-ink/40">Date</dt><dd class="mt-1 font-bold text-brand-blue">{{ $tournament->starts_at?->format('M j, Y') ?? 'TBA' }} @if($tournament->ends_at) - {{ $tournament->ends_at->format('M j, Y') }} @endif</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/40">Venue</dt><dd class="mt-1 font-bold text-brand-blue">{{ $tournament->venue ?: collect([$tournament->city, $tournament->state, $tournament->country])->filter()->join(', ') ?: 'TBA' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/40">Organizer</dt><dd class="mt-1 font-bold text-brand-blue">{{ $tournament->organizer?->name ?? 'Smashr' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/40">Registration</dt><dd class="mt-1 font-bold text-brand-blue">{{ ucfirst($tournament->registration_mode) }} | {{ ucfirst($tournament->registration_status) }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/40">Club</dt><dd class="mt-1 font-bold text-brand-blue">{{ $tournament->club?->name ?? 'Independent' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/40">Matches</dt><dd class="mt-1 font-bold text-brand-blue">{{ $tournament->matches_count }} matches</dd></div>
                </dl>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Registration</p>
                @auth
                    @if ($tournament->registrationOpen() && $tournament->categories->isNotEmpty())
                        <p class="mt-4 text-sm font-bold text-brand-ink/60">Submit your category, contact details, and SmashR KYC document on the dedicated registration page.</p>
                        <a href="{{ route('tournaments.register.form', $tournament) }}" class="mt-5 inline-flex rounded-md bg-brand-blue px-4 py-3 text-sm font-black uppercase text-white">Open registration</a>
                    @else
                        <p class="mt-4 text-sm font-bold text-brand-ink/60">Registration is not open for this tournament.</p>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="mt-5 inline-flex rounded-md bg-brand-blue px-4 py-3 text-sm font-black uppercase text-white">Log in to register</a>
                @endauth
            </section>
        </div>

        <section class="mt-8 rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Categories</p>
                    <h2 class="text-2xl font-black text-brand-blue">Draws and entrants</h2>
                </div>
                <a href="{{ route('tournaments.matches', $tournament) }}" class="w-fit rounded-full border border-brand-ink/10 px-4 py-2 text-xs font-black uppercase text-brand-blue">Matches</a>
            </div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                @forelse ($tournament->categories as $category)
                    <article class="rounded-lg border border-brand-ink/10 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-black text-brand-blue">{{ $category->name }}</h3>
                                <p class="text-sm font-bold text-brand-ink/50">{{ $category->format }} | {{ str_replace('_', ' ', $category->draw_mode) }}</p>
                            </div>
                            <a href="{{ route('tournaments.draw', [$tournament, $category]) }}" class="rounded-md bg-brand-blue px-3 py-2 text-xs font-black uppercase text-white">View draw</a>
                        </div>
                        <div class="mt-4 divide-y divide-brand-ink/10">
                            @forelse ($category->entrants->where('status', 'approved')->take(6) as $entrant)
                                <p class="py-2 text-sm font-bold text-brand-ink/70">{{ $entrant->displayName() }}</p>
                            @empty
                                <p class="py-2 text-sm text-brand-ink/50">No approved entrants yet.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <p class="text-brand-ink/60">No categories have been published yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
