<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Create match</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout title="Create match" subtitle="Create a compact pending match record and assign players, court and schedule metadata.">
            <form method="POST" action="{{ route('admin.matches.store') }}" class="grid gap-4 md:grid-cols-3">
                @csrf
                <label class="font-bold text-brand-blue">Format
                    <select name="format" class="mt-1 w-full rounded-md border-brand-ink/10">
                        <option value="singles">Singles</option>
                        <option value="doubles">Doubles</option>
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Tournament
                    <select name="tournament_id" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="">No tournament</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}">{{ $tournament->name }}</option>@endforeach</select>
                </label>
                <label class="font-bold text-brand-blue">Event
                    <select name="tournament_category_id" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="">No event</option>@foreach($events as $event)<option value="{{ $event->id }}">{{ $event->name }}</option>@endforeach</select>
                </label>
                <label class="font-bold text-brand-blue">Club
                    <select name="club_id" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="">No club</option>@foreach($clubs as $club)<option value="{{ $club->id }}">{{ $club->name }}</option>@endforeach</select>
                </label>
                <label class="font-bold text-brand-blue">Side A player
                    <select name="side_a_user_id" class="mt-1 w-full rounded-md border-brand-ink/10">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) $preselectedUserId === $user->id)>{{ $user->playerProfile?->display_name ?? $user->name }} | {{ $user->email }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Side B player
                    <select name="side_b_user_id" class="mt-1 w-full rounded-md border-brand-ink/10">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->playerProfile?->display_name ?? $user->name }} | {{ $user->email }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Played date<input name="played_at" type="date" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Scheduled time<input name="scheduled_at" type="datetime-local" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Court<input name="court_label" placeholder="Court 1" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Duration<input name="estimated_duration_minutes" type="number" min="5" max="240" value="30" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue">Winner<select name="winner_side" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="A">Side A</option><option value="B">Side B</option></select></label>
                <label class="font-bold text-brand-blue">Status<select name="status" class="mt-1 w-full rounded-md border-brand-ink/10"><option value="pending_confirmation">Pending confirmation</option><option value="disputed">Disputed</option><option value="void">Void</option></select></label>
                <div class="flex gap-3 md:col-span-3">
                    <button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Create match</button>
                    <a href="{{ route('admin.matches') }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a>
                </div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
