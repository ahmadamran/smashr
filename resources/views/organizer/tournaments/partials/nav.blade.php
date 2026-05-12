<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('organizer.tournaments.index') }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.index') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">My tournaments</a>
    <a href="{{ route('organizer.tournaments.create') }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.create') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Create tournament</a>
    @isset($tournament)
        <a href="{{ route('organizer.tournaments.edit', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.edit') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Setup</a>
        <a href="{{ route('organizer.tournaments.edit', $tournament).'#categories' }}" class="rounded-full bg-white px-4 py-2 text-sm font-black uppercase text-brand-blue">Categories</a>
        <a href="{{ route('organizer.tournaments.registrations', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.registrations') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Registrations</a>
        <a href="{{ route('organizer.tournaments.draws', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.draws') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Draws & Schedule</a>
        <a href="{{ route('organizer.tournaments.draw-engine', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.draw-engine*') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Draw Engine</a>
        <a href="{{ route('organizer.tournaments.matches', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.matches') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Match Control</a>
        <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-full bg-white px-4 py-2 text-sm font-black uppercase text-brand-blue">Public Page</a>
    @endisset
</div>
