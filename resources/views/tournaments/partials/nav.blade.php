@php
    $drawCategory = isset($category) && $category instanceof \Modules\Tournaments\Models\TournamentCategory
        ? $category
        : $tournament->categories->first();
    $hasSeededEntrants = $tournament->entrants()->where('status', 'approved')->whereNotNull('seed')->exists();
    $hasWinnerResults = $tournament->matches()->where('status', 'confirmed')->whereNotNull('winner_side')->exists();
    $playersTab = request()->routeIs('tournaments.players') && request('tab') !== 'seeded';
    $seededTab = request()->routeIs('tournaments.players') && request('tab') === 'seeded';
@endphp

<nav class="mb-6 overflow-x-auto">
    <div class="flex min-w-max gap-2">
        <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.show') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Overview</a>
        <a href="{{ route('tournaments.players', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ $playersTab ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Players</a>
        @if ($hasSeededEntrants)
            <a href="{{ route('tournaments.players', ['tournament' => $tournament, 'tab' => 'seeded']) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ $seededTab ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Seeded</a>
        @endif
        @if ($drawCategory)
            <a href="{{ route('tournaments.draw', [$tournament, $drawCategory]) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.draw') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Draws</a>
        @endif
        <a href="{{ route('tournaments.matches', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.matches') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Matches</a>
        @if ($hasWinnerResults)
            <a href="{{ route('tournaments.winners', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.winners') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Winners</a>
        @endif
        @if ($tournament->registrationOpen())
            <a href="{{ route('tournaments.register.form', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.register.form') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Register</a>
        @endif
    </div>
</nav>
