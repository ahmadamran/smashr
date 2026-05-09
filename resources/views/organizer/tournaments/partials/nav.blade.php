<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('organizer.tournaments.index') }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.index') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">My tournaments</a>
    <a href="{{ route('organizer.tournaments.create') }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.create') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Create tournament</a>
    @isset($tournament)
        <a href="{{ route('organizer.tournaments.edit', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.edit') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Setup</a>
        <a href="{{ route('organizer.tournaments.edit', $tournament).'#categories' }}" class="rounded-full bg-white px-4 py-2 text-sm font-black uppercase text-[#071a80]">Categories</a>
        <a href="{{ route('organizer.tournaments.registrations', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.registrations') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Registrations</a>
        <a href="{{ route('organizer.tournaments.draws', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.draws') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Draws & Schedule</a>
        <a href="{{ route('organizer.tournaments.draw-engine', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.draw-engine*') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Draw Engine</a>
        <a href="{{ route('organizer.tournaments.matches', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.matches') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Match Control</a>
        <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-full bg-white px-4 py-2 text-sm font-black uppercase text-[#071a80]">Public Page</a>
    @endisset
</div>
