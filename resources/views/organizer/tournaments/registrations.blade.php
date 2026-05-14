<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <section class="mb-8 rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Registration control</p>
                    <h2 class="text-2xl font-black text-brand-blue">Entrants</h2>
                </div>
                <a href="{{ route('organizer.tournaments.entrants.create', $tournament) }}" class="w-fit rounded-full bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white shadow-sm transition hover:bg-brand-blue-dark">Add entrant</a>
            </div>
            <p class="mt-3 max-w-2xl text-sm font-bold text-brand-ink/60">Review submitted registrations, update approval status, and manage seeding per category.</p>
        </section>

        <div class="grid gap-6">
            @foreach ($tournament->categories as $category)
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <h2 class="text-2xl font-black text-brand-blue">{{ $category->name }}</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($category->entrants as $entrant)
                            <form method="POST" action="{{ route('organizer.tournaments.entrants.update', [$tournament, $entrant]) }}" class="grid items-center gap-3 rounded-md border border-brand-ink/10 p-4 md:grid-cols-5">@csrf @method('PATCH')
                                <div class="md:col-span-2">
                                    <p class="font-black text-brand-blue">{{ $entrant->displayName() ?: 'Unnamed entrant' }}</p>
                                    <p class="text-xs font-bold uppercase text-brand-ink/40">
                                        {{ $entrant->created_at->format('M j, Y') }}
                                        @if ($entrant->seed)
                                            <span class="ml-2 rounded-full bg-brand-surface px-2 py-1 text-brand-blue">Seed {{ $entrant->seed }}</span>
                                        @endif
                                    </p>
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
                                <select name="status" class="rounded-md border-gray-300">
                                    @foreach (['pending','approved','rejected','withdrawn'] as $status)
                                        <option value="{{ $status }}" @selected($entrant->status === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                <input name="seed" type="number" min="1" value="{{ $entrant->seed }}" placeholder="Seed" class="rounded-md border-gray-300">
                                <button class="rounded-md border border-brand-ink/10 px-4 py-2 text-sm font-black uppercase text-brand-blue">Save</button>
                            </form>
                        @empty
                            <p class="text-brand-ink/60">No registrations yet.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
