<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Tournament</p>
                <h1 class="text-3xl font-black text-[#071a80] sm:text-4xl">{{ $tournament->name }}</h1>
            </div>
            @auth
                @if ($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'))
                    <a href="{{ route('organizer.tournaments.edit', $tournament) }}" class="w-fit rounded-full bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">Manage tournament</a>
                @endif
            @endauth
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Info</p>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-black uppercase text-blue-950/40">Date</dt><dd class="mt-1 font-bold text-[#071a80]">{{ $tournament->starts_at?->format('M j, Y') ?? 'TBA' }} @if($tournament->ends_at) - {{ $tournament->ends_at->format('M j, Y') }} @endif</dd></div>
                    <div><dt class="text-xs font-black uppercase text-blue-950/40">Venue</dt><dd class="mt-1 font-bold text-[#071a80]">{{ $tournament->venue ?: collect([$tournament->city, $tournament->state, $tournament->country])->filter()->join(', ') ?: 'TBA' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-blue-950/40">Organizer</dt><dd class="mt-1 font-bold text-[#071a80]">{{ $tournament->organizer?->name ?? 'Smashr' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-blue-950/40">Registration</dt><dd class="mt-1 font-bold text-[#071a80]">{{ ucfirst($tournament->registration_mode) }} | {{ ucfirst($tournament->registration_status) }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-blue-950/40">Club</dt><dd class="mt-1 font-bold text-[#071a80]">{{ $tournament->club?->name ?? 'Independent' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-blue-950/40">Matches</dt><dd class="mt-1 font-bold text-[#071a80]">{{ $tournament->matches_count }} matches</dd></div>
                </dl>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Registration</p>
                @auth
                    @if ($tournament->registrationOpen() && $tournament->categories->isNotEmpty())
                        <form method="POST" action="{{ route('tournaments.register', $tournament) }}" class="mt-5 grid gap-3">@csrf
                            <select name="tournament_category_id" class="rounded-md border-blue-950/10">
                                @foreach ($tournament->categories->where('status', 'published') as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} | {{ str_replace('_', ' ', $category->draw_mode) }}</option>
                                @endforeach
                            </select>
                            <input name="partner_email" placeholder="Partner email for doubles/mixed" class="rounded-md border-blue-950/10">
                            <input name="partner_name" placeholder="Partner name if not a Smashr user" class="rounded-md border-blue-950/10">
                            <button class="rounded-md bg-[#071a80] px-4 py-3 text-sm font-black uppercase text-white">Request registration</button>
                        </form>
                    @else
                        <p class="mt-4 text-sm font-bold text-blue-950/60">Registration is not open for this tournament.</p>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="mt-5 inline-flex rounded-md bg-[#071a80] px-4 py-3 text-sm font-black uppercase text-white">Log in to register</a>
                @endauth
            </section>
        </div>

        <section class="mt-8 rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Categories</p>
                    <h2 class="text-2xl font-black text-[#071a80]">Draws and entrants</h2>
                </div>
                <a href="{{ route('tournaments.matches', $tournament) }}" class="w-fit rounded-full border border-blue-950/10 px-4 py-2 text-xs font-black uppercase text-[#071a80]">Match schedule</a>
            </div>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                @forelse ($tournament->categories as $category)
                    <article class="rounded-lg border border-blue-950/10 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-xl font-black text-[#071a80]">{{ $category->name }}</h3>
                                <p class="text-sm font-bold text-blue-950/50">{{ $category->format }} | {{ str_replace('_', ' ', $category->draw_mode) }}</p>
                            </div>
                            <a href="{{ route('tournaments.draw', [$tournament, $category]) }}" class="rounded-md bg-[#071a80] px-3 py-2 text-xs font-black uppercase text-white">View draw</a>
                        </div>
                        <div class="mt-4 divide-y divide-blue-950/10">
                            @forelse ($category->entrants->where('status', 'approved')->take(6) as $entrant)
                                <p class="py-2 text-sm font-bold text-blue-950/70">{{ $entrant->displayName() }}</p>
                            @empty
                                <p class="py-2 text-sm text-blue-950/50">No approved entrants yet.</p>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <p class="text-blue-950/60">No categories have been published yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
