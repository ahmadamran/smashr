<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Edit match #{{ $match->id }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout title="Match controls" subtitle="Edit result metadata, assign court, reschedule, confirm, dispute or void from this page.">
            <form method="POST" action="{{ route('admin.matches.update', $match) }}" class="grid gap-4 md:grid-cols-3">
                @csrf @method('PATCH')
                <label class="font-bold text-brand-blue">Format<select name="format" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="singles" @selected($match->format === 'singles')>Singles</option><option value="doubles" @selected($match->format === 'doubles')>Doubles</option><option value="mixed" @selected($match->format === 'mixed')>Mixed</option></select></label>
                <label class="font-bold text-brand-blue">Tournament<select name="tournament_id" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="">No tournament</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}" @selected($match->tournament_id === $tournament->id)>{{ $tournament->name }}</option>@endforeach</select></label>
                <label class="font-bold text-brand-blue">Event<select name="tournament_category_id" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="">No event</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected($match->tournament_category_id === $event->id)>{{ $event->name }}</option>@endforeach</select></label>
                <label class="font-bold text-brand-blue">Club<select name="club_id" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="">No club</option>@foreach($clubs as $club)<option value="{{ $club->id }}" @selected($match->club_id === $club->id)>{{ $club->name }}</option>@endforeach</select></label>
                <label class="font-bold text-brand-blue">Played date<input name="played_at" type="date" value="{{ $match->played_at?->toDateString() }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Scheduled time<input name="scheduled_at" type="datetime-local" value="{{ $match->scheduled_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Court<input name="court_label" value="{{ $match->court_label }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Winner<select name="winner_side" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="A" @selected($match->winner_side === 'A')>Side A</option><option value="B" @selected($match->winner_side === 'B')>Side B</option></select></label>
                <label class="font-bold text-brand-blue">Status<select name="status" class="mt-1 w-full rounded-md border-brand-ink/10">@foreach(['pending_confirmation','confirmed','disputed','void'] as $status)<option value="{{ $status }}" @selected($match->status === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select></label>
                <div class="flex gap-3 md:col-span-3"><button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Save match</button><a href="{{ route('admin.matches') }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a></div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
