@php
    $selectedEventId = old('event_id', $selectedEvent->id ?? $tournament->categories->first()?->id);
    $selectedDrawType = old('draw_type', $preview['draw_type'] ?? 'single_elimination');
    $defaultDate = $tournament->starts_at?->toDateString() ?? now()->toDateString();
@endphp

<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Draw Engine | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="mb-6 rounded bg-red-50 p-4 font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('organizer.tournaments.draw-engine.preview', $tournament) }}" class="grid gap-6">
            @csrf
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Step 1</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">Select tournament event</h2>
                <select name="event_id" class="mt-4 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    @foreach ($tournament->categories as $event)
                        <option value="{{ $event->id }}" @selected((int) $selectedEventId === $event->id)>{{ $event->name }} | {{ $event->approvedEntrants->count() }} approved participants</option>
                    @endforeach
                </select>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Step 2</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">Select draw type</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    @foreach ($drawTypes as $drawType)
                        <label class="rounded-md border border-blue-950/10 p-4 text-sm font-bold text-blue-950/70">
                            <input type="radio" name="draw_type" value="{{ $drawType->value }}" @checked($selectedDrawType === $drawType->value) class="mr-2 text-[#071a80]">
                            {{ $drawType->label() }}
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Step 3</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">Configure draw settings</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <label class="text-sm font-black text-[#071a80]">Group / pool size
                        <input name="group_size" type="number" min="3" max="8" value="{{ old('group_size', 4) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Qualifiers per pool
                        <input name="qualifiers_per_pool" type="number" min="1" max="4" value="{{ old('qualifiers_per_pool', 2) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Event priority
                        <input name="event_priority" type="number" min="1" value="{{ old('event_priority', 1) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Stage priority
                        <input name="stage_priority" type="number" min="1" value="{{ old('stage_priority', 1) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Step 4</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">Configure scheduling limits by day</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-6">
                    <label class="text-sm font-black text-[#071a80]">Courts
                        <input name="courts_count" type="number" min="1" max="50" value="{{ old('courts_count', 2) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Court label
                        <input name="court_label_prefix" value="{{ old('court_label_prefix', 'Court') }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Start
                        <input name="schedule_start_time" type="time" value="{{ old('schedule_start_time', '09:00') }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">End
                        <input name="schedule_end_time" type="time" value="{{ old('schedule_end_time', '18:00') }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Duration
                        <input name="match_duration_minutes" type="number" min="5" max="240" value="{{ old('match_duration_minutes', 30) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                    <label class="text-sm font-black text-[#071a80]">Rest
                        <input name="rest_minutes" type="number" min="0" max="120" value="{{ old('rest_minutes', 10) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold">
                    </label>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ([1 => 'Pool matches only', 2 => 'Knockout round 1 / QF', 3 => 'Semifinal / Final'] as $index => $label)
                        <div class="rounded-md border border-blue-950/10 p-4">
                            <p class="font-black text-[#071a80]">Day {{ $index }}: {{ $label }}</p>
                            <input type="date" name="days[{{ $index - 1 }}][date]" value="{{ old("days.".($index - 1).".date", now()->parse($defaultDate)->addDays($index - 1)->toDateString()) }}" class="mt-3 w-full rounded-md border-blue-950/10 text-sm font-bold">
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <input type="time" name="days[{{ $index - 1 }}][start_time]" value="{{ old("days.".($index - 1).".start_time", '09:00') }}" class="rounded-md border-blue-950/10 text-sm font-bold">
                                <input type="time" name="days[{{ $index - 1 }}][end_time]" value="{{ old("days.".($index - 1).".end_time", '18:00') }}" class="rounded-md border-blue-950/10 text-sm font-bold">
                            </div>
                            <input type="number" name="days[{{ $index - 1 }}][courts_count]" min="1" value="{{ old("days.".($index - 1).".courts_count", 2) }}" class="mt-3 w-full rounded-md border-blue-950/10 text-sm font-bold">
                            <div class="mt-3 grid gap-2 text-xs font-bold text-blue-950/70">
                                @foreach (['pool', 'knockout', 'main', 'winners', 'losers'] as $stage)
                                    <label><input type="checkbox" name="days[{{ $index - 1 }}][allowed_stages][]" value="{{ $stage }}" @checked(($index === 1 && $stage === 'pool') || ($index > 1 && in_array($stage, ['knockout', 'main', 'winners', 'losers'], true)))> {{ ucfirst($stage) }}</label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button class="rounded-md bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">Preview generated draw</button>
            </div>
        </form>

        @if ($preview)
            <section class="mt-8 rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Step 5</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a80]">Preview generated draw</h2>
                @if (! empty($preview['warnings']))
                    <div class="mt-4 rounded-md bg-amber-50 p-4 text-sm font-bold text-amber-900">
                        {{ implode(' ', $preview['warnings']) }}
                    </div>
                @endif
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach ($preview['matches'] as $match)
                        <article class="rounded-md border border-blue-950/10 p-4">
                            <p class="text-xs font-black uppercase text-[#d6a31d]">{{ $match['stage'] }} | {{ $match['round_label'] }} | Match {{ $match['position'] }}</p>
                            <p class="mt-2 font-black text-[#071a80]">{{ $match['side_a']?->displayName() ?? ($match['feed_rule'] ?: 'TBA') }}</p>
                            <p class="mt-1 font-black text-[#071a80]">{{ $match['side_b']?->displayName() ?? ($match['is_bye'] ? 'BYE' : 'TBA') }}</p>
                            <p class="mt-2 text-xs font-bold text-blue-950/50">{{ $match['court_label'] ?? 'Unscheduled' }} {{ $match['scheduled_at'] ? '| '.$match['scheduled_at']->format('M j, g:i A') : '' }}</p>
                        </article>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('organizer.tournaments.draw-engine.generate', $tournament) }}" class="mt-6 rounded-md bg-[#f8fafc] p-4">
                    @csrf
                    @foreach (['event_id', 'draw_type', 'group_size', 'qualifiers_per_pool', 'courts_count', 'court_label_prefix', 'schedule_start_time', 'schedule_end_time', 'match_duration_minutes', 'rest_minutes', 'max_matches_per_player_per_day'] as $field)
                        <input type="hidden" name="{{ $field }}" value="{{ request($field) }}">
                    @endforeach
                    @foreach ((array) request('days', []) as $dayIndex => $day)
                        @foreach (['date', 'start_time', 'end_time', 'courts_count'] as $field)
                            <input type="hidden" name="days[{{ $dayIndex }}][{{ $field }}]" value="{{ $day[$field] ?? '' }}">
                        @endforeach
                        @foreach ((array) ($day['allowed_stages'] ?? []) as $stage)
                            <input type="hidden" name="days[{{ $dayIndex }}][allowed_stages][]" value="{{ $stage }}">
                        @endforeach
                        @foreach ((array) ($day['allowed_rounds'] ?? []) as $round)
                            <input type="hidden" name="days[{{ $dayIndex }}][allowed_rounds][]" value="{{ $round }}">
                        @endforeach
                    @endforeach
                    <label class="flex items-center gap-2 text-sm font-bold text-blue-950/70">
                        <input type="checkbox" name="confirm_overwrite" value="1">
                        Safe overwrite existing matches for this event
                    </label>
                    <button class="mt-4 rounded-md bg-green-700 px-5 py-3 text-xs font-black uppercase text-white">Generate matches</button>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
