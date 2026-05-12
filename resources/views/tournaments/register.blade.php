<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Tournament registration</p>
                <h1 class="text-3xl font-black text-brand-blue sm:text-4xl">{{ $tournament->name }}</h1>
            </div>
            <a href="{{ route('tournaments.show', $tournament) }}" class="w-fit rounded-full border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Back to tournament</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('tournaments.partials.nav', ['tournament' => $tournament])
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm font-bold text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[.75fr_1.25fr]">
            <aside class="rounded-lg bg-brand-blue p-6 text-white shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-mist">SmashR KYC</p>
                <h2 class="mt-3 text-2xl font-black">Confirm player identity before tournament approval.</h2>
                <dl class="mt-6 space-y-4 text-sm">
                    <div>
                        <dt class="font-black uppercase text-white/50">Tournament</dt>
                        <dd class="mt-1 font-bold">{{ $tournament->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-black uppercase text-white/50">Venue</dt>
                        <dd class="mt-1 font-bold">{{ $tournament->venue ?: collect([$tournament->city, $tournament->state, $tournament->country])->filter()->join(', ') ?: 'TBA' }}</dd>
                    </div>
                    <div>
                        <dt class="font-black uppercase text-white/50">Status</dt>
                        <dd class="mt-1 font-bold">{{ ucfirst($tournament->registration_status) }}</dd>
                    </div>
                </dl>
            </aside>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                @if ($tournament->registrationOpen() && $tournament->categories->where('status', 'published')->isNotEmpty())
                    <form method="POST" action="{{ route('tournaments.register', $tournament) }}" enctype="multipart/form-data" class="grid gap-5">
                        @csrf

                        <div>
                            <label for="tournament_category_id" class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Category</label>
                            <select id="tournament_category_id" name="tournament_category_id" class="mt-2 w-full rounded-md border-brand-ink/10" required>
                                @foreach ($tournament->categories->where('status', 'published') as $category)
                                    <option value="{{ $category->id }}" @selected(old('tournament_category_id') == $category->id)>{{ $category->name }} | {{ str_replace('_', ' ', $category->draw_mode) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="contact_name" class="text-sm font-black text-brand-blue">Full name</label>
                                <input id="contact_name" name="contact_name" value="{{ old('contact_name', auth()->user()->name) }}" class="mt-2 w-full rounded-md border-brand-ink/10" required>
                            </div>
                            <div>
                                <label for="contact_phone" class="text-sm font-black text-brand-blue">Phone number</label>
                                <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" class="mt-2 w-full rounded-md border-brand-ink/10" placeholder="+60..." required>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-[.7fr_1.3fr]">
                            <div>
                                <label for="identity_type" class="text-sm font-black text-brand-blue">Document type</label>
                                <select id="identity_type" name="identity_type" class="mt-2 w-full rounded-md border-brand-ink/10" required>
                                    <option value="ic" @selected(old('identity_type') === 'ic')>IC</option>
                                    <option value="passport" @selected(old('identity_type') === 'passport')>Passport</option>
                                </select>
                            </div>
                            <div>
                                <label for="identity_number" class="text-sm font-black text-brand-blue">IC / passport number</label>
                                <input id="identity_number" name="identity_number" value="{{ old('identity_number') }}" class="mt-2 w-full rounded-md border-brand-ink/10" required>
                            </div>
                        </div>

                        <div>
                            <label for="identity_document" class="text-sm font-black text-brand-blue">IC / passport upload</label>
                            <input id="identity_document" name="identity_document" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-2 w-full rounded-md border border-brand-ink/10 p-3 text-sm" required>
                            <p class="mt-2 text-xs font-bold text-brand-ink/50">Accepted: JPG, PNG, or PDF up to 5MB. Stored privately for organizer review.</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="partner_email" class="text-sm font-black text-brand-blue">Partner email</label>
                                <input id="partner_email" name="partner_email" value="{{ old('partner_email') }}" class="mt-2 w-full rounded-md border-brand-ink/10" placeholder="For doubles/mixed">
                            </div>
                            <div>
                                <label for="partner_name" class="text-sm font-black text-brand-blue">Partner name</label>
                                <input id="partner_name" name="partner_name" value="{{ old('partner_name') }}" class="mt-2 w-full rounded-md border-brand-ink/10" placeholder="If not a SmashR user">
                            </div>
                        </div>

                        <button class="rounded-md bg-brand-blue px-5 py-4 text-sm font-black uppercase text-white">Submit registration</button>
                    </form>
                @else
                    <h2 class="text-2xl font-black text-brand-blue">Registration is not open</h2>
                    <p class="mt-2 text-brand-ink/60">This tournament is not accepting public registration right now.</p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
