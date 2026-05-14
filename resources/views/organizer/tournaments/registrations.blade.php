<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <section class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Registration control</p>
                    <h2 class="text-2xl font-black text-brand-blue">Entrants</h2>
                </div>
                <a href="{{ route('organizer.tournaments.entrants.create', $tournament) }}" class="w-fit rounded-full bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white shadow-sm transition hover:bg-brand-blue-dark">Add entrant</a>
            </div>
            <p class="mt-3 max-w-2xl text-sm font-bold text-brand-ink/60">Review submitted registrations, update approval status, and manage seeding per category.</p>
        </section>

        <section class="mb-6 rounded-lg bg-white p-5 shadow-lg">
            <form method="GET">
                @if ($registrationCategory !== '')
                    <input type="hidden" name="category" value="{{ $registrationCategory }}">
                @endif
                <div class="grid gap-3 md:grid-cols-[minmax(13rem,1.4fr)_minmax(8rem,.7fr)_auto] md:items-end">
                    <input
                        id="registration-search"
                        name="search"
                        type="search"
                        value="{{ $registrationSearch }}"
                        placeholder="Search players or school"
                        class="rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink placeholder:text-brand-ink/40"
                    >

                    <select name="gender" class="rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink" onchange="this.form.submit()">
                        <option value="">All genders</option>
                        <option value="male" @selected($registrationGender === 'male')>Men</option>
                        <option value="female" @selected($registrationGender === 'female')>Women</option>
                    </select>

                    @if ($hasRegistrationFilters)
                        <a href="{{ route('organizer.tournaments.registrations', $tournament) }}" class="rounded-md border border-brand-ink/10 px-4 py-2 text-center text-sm font-black uppercase text-brand-blue">
                            Clear
                        </a>
                    @else
                        <span class="hidden md:block"></span>
                    @endif
                </div>
            </form>

            <div class="mt-5 overflow-x-auto">
                <div class="flex min-w-max gap-2">
                    @php
                        $baseRegistrationQuery = collect([
                            'search' => $registrationSearch,
                            'gender' => $registrationGender,
                        ])->filter(fn ($value) => filled($value))->all();
                    @endphp
                    <a href="{{ route('organizer.tournaments.registrations', [$tournament, ...$baseRegistrationQuery]) }}" class="rounded-md px-4 py-2 text-xs font-black uppercase {{ $registrationCategory === '' ? 'bg-brand-blue text-white' : 'bg-brand-surface text-brand-blue' }}">
                        All
                    </a>
                    @foreach ($tournament->categories as $option)
                        <a href="{{ route('organizer.tournaments.registrations', [$tournament, ...$baseRegistrationQuery, 'category' => $option->id]) }}" class="rounded-md px-4 py-2 text-xs font-black uppercase {{ $registrationCategory === (string) $option->id ? 'bg-brand-blue text-white' : 'bg-brand-surface text-brand-blue' }}">
                            {{ $option->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mb-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-5 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Players</p>
                <p class="mt-2 text-3xl font-black text-brand-blue">{{ $filteredPlayerCount }}</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Entrants</p>
                <p class="mt-2 text-3xl font-black text-brand-blue">{{ $filteredEntrantCount }}</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-green">Categories</p>
                <p class="mt-2 text-3xl font-black text-brand-blue">{{ $registrationCategories->count() }}</p>
            </div>
        </section>

        <div class="grid gap-6">
            @forelse ($registrationCategories as $category)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-brand-blue">{{ $category->name }}</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($category->entrants as $entrant)
                            <article class="flex flex-col gap-4 rounded-md border border-brand-ink/10 p-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <p class="font-black text-brand-blue">{{ $entrant->displayName() ?: 'Unnamed entrant' }}</p>
                                    <p class="text-xs font-bold uppercase text-brand-ink/40">
                                        {{ $entrant->created_at->format('M j, Y') }}
                                        <span class="ml-2 rounded-full bg-brand-surface px-2 py-1 text-brand-blue">{{ $entrant->status }}</span>
                                        @if ($entrant->seed)
                                            <span class="ml-2 rounded-full bg-brand-surface px-2 py-1 text-brand-blue">Seed {{ $entrant->seed }}</span>
                                        @endif
                                    </p>
                                    @if ($entrant->players->isNotEmpty())
                                        <div class="mt-3 space-y-2">
                                            @foreach ($entrant->players as $player)
                                                @php
                                                    $ranking = $player->user_id ? data_get($rankingByUserAndFormat, "{$category->format}.{$player->user_id}") : null;
                                                    $formatLabel = ucfirst($category->format);
                                                @endphp
                                                <div class="rounded-md bg-brand-surface p-3 text-xs font-bold text-brand-ink/60">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-black text-brand-blue">{{ $player->displayName() }}</span>
                                                        <span class="rounded-full bg-white px-2 py-1 font-black uppercase text-brand-blue">{{ $formatLabel }} Smashr ranking</span>
                                                    </div>
                                                    @if ($ranking)
                                                        <p class="mt-2">
                                                            <span class="font-black text-brand-blue">{{ $ranking['rank'] ? '#'.$ranking['rank'] : 'Unrated' }}</span>
                                                            <span class="mx-1 text-brand-ink/30">|</span>
                                                            Rating {{ $ranking['matches'] > 0 ? $ranking['rating'] : 'Unrated' }}
                                                            <span class="mx-1 text-brand-ink/30">|</span>
                                                            {{ $ranking['matches'] }} matches
                                                            <span class="mx-1 text-brand-ink/30">|</span>
                                                            {{ $ranking['points'] }} points
                                                        </p>
                                                    @else
                                                        <p class="mt-2">No linked Smashr profile</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($entrant->contact_name || $entrant->contact_phone || $entrant->identity_number)
                                        <div class="mt-3 rounded-md bg-brand-surface p-3 text-xs font-bold text-brand-ink/60">
                                            <p>{{ $entrant->contact_name ?: 'No contact name' }} @if($entrant->contact_phone) | {{ $entrant->contact_phone }} @endif</p>
                                            <p class="mt-1 uppercase">{{ $entrant->identity_type ?: 'KYC' }}: {{ $entrant->identity_number ?: 'Not provided' }} | {{ $entrant->kyc_status }}</p>
                                            @if ($entrant->identity_document_path)
                                                <a href="{{ route('organizer.tournaments.entrants.document', [$tournament, $entrant]) }}" class="mt-2 inline-flex text-brand-blue underline">Download KYC document</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('organizer.tournaments.entrants.edit', [$tournament, $entrant, 'return_url' => request()->fullUrl()]) }}" class="shrink-0 rounded-md border border-brand-ink/10 px-4 py-2 text-center text-sm font-black uppercase text-brand-blue">Edit</a>
                            </article>
                        @empty
                            <p class="text-brand-ink/60">No registrations yet.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <section class="rounded-lg bg-white p-10 text-center font-bold text-brand-ink/60 shadow-lg">
                    No registrations match your filters.
                </section>
            @endforelse
        </div>
    </div>
</x-app-layout>
