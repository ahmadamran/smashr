@php
    $drawCategory = $category ?? $tournament->categories->first();
@endphp

<nav class="mb-6 overflow-x-auto">
    <div class="flex min-w-max gap-2">
        <a href="{{ route('tournaments.show', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.show') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Overview</a>
        <a href="{{ route('tournaments.players', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.players') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Players</a>
        @if ($drawCategory)
            <a href="{{ route('tournaments.draw', [$tournament, $drawCategory]) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.draw') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Draws</a>
        @endif
        <a href="{{ route('tournaments.matches', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.matches') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Matches</a>
        <a href="{{ route('tournaments.matches', $tournament).'#live' }}" class="rounded-full bg-white px-4 py-2 text-xs font-black uppercase text-brand-blue">Live Scores</a>
        <a href="{{ route('matches.index', ['tournament' => $tournament->slug, 'status' => 'confirmed']) }}" class="rounded-full bg-white px-4 py-2 text-xs font-black uppercase text-brand-blue">Results</a>
        @if ($tournament->registrationOpen())
            <a href="{{ route('tournaments.register.form', $tournament) }}" class="rounded-full px-4 py-2 text-xs font-black uppercase {{ request()->routeIs('tournaments.register.form') ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">Register</a>
        @endif
    </div>
</nav>
