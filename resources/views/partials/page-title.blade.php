@php
    $brand = 'SMASHR';
    $tagline = 'Badminton ratings, draws and live scores';
    $explicitTitle = isset($title) ? trim((string) $title) : null;

    $routeTitle = match (true) {
        request()->routeIs('tournaments.draw') => trim((request()->route('category')?->name ?? 'Tournament').' Draw'),
        request()->routeIs('tournaments.matches') => trim((request()->route('tournament')?->name ?? 'Tournament').' Matches'),
        request()->routeIs('tournaments.show') => request()->route('tournament')?->name,
        request()->routeIs('tournaments.register.form') => trim((request()->route('tournament')?->name ?? 'Tournament').' Registration'),
        request()->routeIs('clubs.show') => request()->route('club')?->name,
        request()->routeIs('players.show') => request()->route('playerProfile')?->display_name,
        request()->routeIs('rankings') => ucfirst(request('format', 'singles')).' Rankings',
        request()->routeIs('matches.index') => 'Submitted Matches',
        request()->routeIs('clubs.index') => 'Clubs',
        request()->routeIs('tournaments.index') => 'Tournaments',
        request()->routeIs('dashboard') => 'Dashboard',
        request()->routeIs('login') => 'Login',
        request()->routeIs('register') => 'Create Account',
        default => str(request()->route()?->getName() ?? 'Home')->replace(['.', '-'], ' ')->title()->toString(),
    };

    $pageInfo = $explicitTitle ?: $routeTitle;
    $fullTitle = collect([$brand, $tagline, $pageInfo])->filter()->join(' - ');
@endphp
{{ $fullTitle }}
