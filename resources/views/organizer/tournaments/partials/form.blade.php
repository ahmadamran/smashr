<input name="name" value="{{ old('name', $tournament?->name) }}" placeholder="Tournament name" class="rounded-md border-gray-300 md:col-span-2">
<select name="club_id" class="rounded-md border-gray-300">
    <option value="">No club</option>
    @foreach ($clubs as $club)
        <option value="{{ $club->id }}" @selected(old('club_id', $tournament?->club_id) == $club->id)>{{ $club->name }}</option>
    @endforeach
</select>
<input name="venue" value="{{ old('venue', $tournament?->venue) }}" placeholder="Venue" class="rounded-md border-gray-300">
<input name="country" value="{{ old('country', $tournament?->country) }}" placeholder="Country" class="rounded-md border-gray-300">
<input name="state" value="{{ old('state', $tournament?->state) }}" placeholder="State" class="rounded-md border-gray-300">
<input name="city" value="{{ old('city', $tournament?->city) }}" placeholder="City" class="rounded-md border-gray-300">
<input name="starts_at" type="date" value="{{ old('starts_at', $tournament?->starts_at?->toDateString()) }}" class="rounded-md border-gray-300">
<input name="ends_at" type="date" value="{{ old('ends_at', $tournament?->ends_at?->toDateString()) }}" class="rounded-md border-gray-300">
<select name="status" class="rounded-md border-gray-300">
    @foreach (['draft', 'published', 'archived'] as $status)
        <option value="{{ $status }}" @selected(old('status', $tournament?->status ?? 'draft') === $status)>{{ $status }}</option>
    @endforeach
</select>
<select name="registration_mode" class="rounded-md border-gray-300">
    @foreach (['public', 'private', 'invitation'] as $mode)
        <option value="{{ $mode }}" @selected(old('registration_mode', $tournament?->registration_mode ?? 'public') === $mode)>{{ $mode }} registration</option>
    @endforeach
</select>
<select name="registration_status" class="rounded-md border-gray-300">
    @foreach (['open', 'closed'] as $status)
        <option value="{{ $status }}" @selected(old('registration_status', $tournament?->registration_status ?? 'open') === $status)>registration {{ $status }}</option>
    @endforeach
</select>
<input name="registration_deadline" type="date" value="{{ old('registration_deadline', $tournament?->registration_deadline?->toDateString()) }}" class="rounded-md border-gray-300">
<x-input-error :messages="$errors->all()" class="md:col-span-2" />
