<div class="md:col-span-2">
    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Tournament details</p>
    <p class="mt-2 text-sm font-bold text-brand-ink/60">These details appear on the public tournament page and organizer tools.</p>
</div>

<label class="font-bold text-brand-blue md:col-span-2">
    Tournament name
    <input name="name" value="{{ old('name', $tournament?->name) }}" placeholder="Tournament name" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<label class="font-bold text-brand-blue">
    Club
    <select name="club_id" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
        <option value="">No club</option>
        @foreach ($clubs as $club)
            <option value="{{ $club->id }}" @selected(old('club_id', $tournament?->club_id) == $club->id)>{{ $club->name }}</option>
        @endforeach
    </select>
</label>

<label class="font-bold text-brand-blue">
    Venue
    <input name="venue" value="{{ old('venue', $tournament?->venue) }}" placeholder="Venue" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<label class="font-bold text-brand-blue">
    Country
    <input name="country" value="{{ old('country', $tournament?->country) }}" placeholder="Country" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<label class="font-bold text-brand-blue">
    State
    <input name="state" value="{{ old('state', $tournament?->state) }}" placeholder="State" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<label class="font-bold text-brand-blue">
    City
    <input name="city" value="{{ old('city', $tournament?->city) }}" placeholder="City" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<div class="md:col-span-2 border-t border-brand-ink/10 pt-2">
    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">Schedule and publishing</p>
</div>

<label class="font-bold text-brand-blue">
    Start date
    <input name="starts_at" type="date" value="{{ old('starts_at', $tournament?->starts_at?->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<label class="font-bold text-brand-blue">
    End date
    <input name="ends_at" type="date" value="{{ old('ends_at', $tournament?->ends_at?->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
</label>

<label class="font-bold text-brand-blue">
    Tournament status
    <select name="status" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
        @foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $tournament?->status ?? 'draft') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs font-bold text-brand-ink/50">Controls whether the tournament is published or archived.</span>
</label>

<label class="font-bold text-brand-blue">
    Registration mode
    <select name="registration_mode" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
        @foreach (['public' => 'Public registration', 'private' => 'Private registration', 'invitation' => 'Invitation registration'] as $value => $label)
            <option value="{{ $value }}" @selected(old('registration_mode', $tournament?->registration_mode ?? 'public') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs font-bold text-brand-ink/50">Controls who can submit registrations.</span>
</label>

<label class="font-bold text-brand-blue">
    Registration status
    <select name="registration_status" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
        @foreach (['open' => 'Registration open', 'closed' => 'Registration closed'] as $value => $label)
            <option value="{{ $value }}" @selected(old('registration_status', $tournament?->registration_status ?? 'open') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs font-bold text-brand-ink/50">Opens or closes the registration form.</span>
</label>

<label class="font-bold text-brand-blue">
    Registration deadline
    <input name="registration_deadline" type="date" value="{{ old('registration_deadline', $tournament?->registration_deadline?->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 text-brand-ink">
    <span class="mt-1 block text-xs font-bold text-brand-ink/50">Optional last day for players to register.</span>
</label>

<x-input-error :messages="$errors->all()" class="md:col-span-2" />
