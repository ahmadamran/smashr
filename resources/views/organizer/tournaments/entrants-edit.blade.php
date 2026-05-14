<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <section class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Registration control</p>
                    <h2 class="text-2xl font-black text-brand-blue">{{ $entrant->displayName() ?: 'Unnamed entrant' }}</h2>
                    <p class="mt-2 text-sm font-bold text-brand-ink/60">{{ $entrant->category?->name ?? 'Unassigned category' }}</p>
                </div>
                <a href="{{ $returnUrl ?: route('organizer.tournaments.registrations', $tournament) }}" class="w-fit rounded-md border border-brand-ink/10 px-4 py-2 text-sm font-black uppercase text-brand-blue">Back</a>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Players</p>
                <div class="mt-5 grid gap-3">
                    @foreach ($entrant->players as $player)
                        @php
                            $ranking = $playerRankings->get($player->id);
                            $formatLabel = ucfirst($entrant->category?->format ?? 'singles');
                        @endphp
                        <article class="rounded-md bg-brand-surface p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-black text-brand-blue">{{ $player->displayName() }}</p>
                                <span class="rounded-full bg-white px-2 py-1 text-xs font-black uppercase text-brand-blue">{{ $formatLabel }} Smashr ranking</span>
                            </div>
                            @if ($player->school_name)
                                <p class="mt-1 text-sm font-bold text-brand-ink/55">{{ $player->school_name }}</p>
                            @endif
                            @if ($ranking)
                                <p class="mt-3 text-sm font-bold text-brand-ink/60">
                                    <span class="font-black text-brand-blue">{{ $ranking['rank'] ? '#'.$ranking['rank'] : 'Unrated' }}</span>
                                    <span class="mx-1 text-brand-ink/30">|</span>
                                    Rating {{ $ranking['matches'] > 0 ? $ranking['rating'] : 'Unrated' }}
                                    <span class="mx-1 text-brand-ink/30">|</span>
                                    {{ $ranking['matches'] }} matches
                                    <span class="mx-1 text-brand-ink/30">|</span>
                                    {{ $ranking['points'] }} points
                                </p>
                            @else
                                <p class="mt-3 text-sm font-bold text-brand-ink/60">No linked Smashr profile</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Edit entrant</p>
                <form method="POST" action="{{ route('organizer.tournaments.entrants.update', [$tournament, $entrant]) }}" class="mt-5 grid gap-4">
                    @csrf
                    @method('PATCH')
                    <label class="text-sm font-black uppercase text-brand-blue">
                        Status
                        <select name="status" class="mt-2 w-full rounded-md border-brand-ink/10 text-sm font-bold normal-case text-brand-ink">
                            @foreach (['pending','approved','rejected','withdrawn'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $entrant->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-black uppercase text-brand-blue">
                        Seed
                        <input name="seed" type="number" min="1" value="{{ old('seed', $entrant->seed) }}" placeholder="No seed" class="mt-2 w-full rounded-md border-brand-ink/10 text-sm font-bold normal-case text-brand-ink">
                    </label>
                    <button class="rounded-md bg-brand-blue px-4 py-3 text-sm font-black uppercase text-white">Save changes</button>
                </form>

                <div class="mt-6 rounded-md bg-brand-surface p-4 text-sm font-bold text-brand-ink/60">
                    <p class="text-xs font-black uppercase text-brand-green">Registration details</p>
                    <p class="mt-3">{{ $entrant->contact_name ?: 'No contact name' }}</p>
                    @if ($entrant->contact_phone)
                        <p>{{ $entrant->contact_phone }}</p>
                    @endif
                    <p class="mt-2 uppercase">{{ $entrant->identity_type ?: 'KYC' }}: {{ $entrant->identity_number ?: 'Not provided' }}</p>
                    <p class="uppercase">KYC: {{ $entrant->kyc_status }}</p>
                    @if ($entrant->identity_document_path)
                        <a href="{{ route('organizer.tournaments.entrants.document', [$tournament, $entrant]) }}" class="mt-3 inline-flex text-brand-blue underline">Download KYC document</a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
