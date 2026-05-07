<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('organizer.tournaments.index') }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.index') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">My tournaments</a>
    <a href="{{ route('organizer.tournaments.create') }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.create') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Create</a>
    @isset($tournament)
        <a href="{{ route('organizer.tournaments.edit', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.edit') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Settings</a>
        <a href="{{ route('organizer.tournaments.registrations', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.registrations') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Registrations</a>
        <a href="{{ route('organizer.tournaments.draws', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.draws') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Draws</a>
        <a href="{{ route('organizer.tournaments.matches', $tournament) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs('organizer.tournaments.matches') ? 'bg-[#071a80] text-white' : 'bg-white text-[#071a80]' }}">Matches</a>
    @endisset
</div>
