@php
    use Modules\Players\Models\PlayerProfile;
    use Modules\Matches\Models\MatchRecord;
    use Modules\Clubs\Models\Club;
    use Modules\Tournaments\Models\Tournament;
    use Illuminate\Support\Facades\Schema;

    $topSingles = Schema::hasTable('player_profiles')
        ? PlayerProfile::where('singles_matches', '>', 0)->orderByDesc('singles_rating')->limit(3)->get()
        : collect();
    $topDoubles = Schema::hasTable('player_profiles')
        ? PlayerProfile::where('doubles_matches', '>', 0)->orderByDesc('doubles_rating')->limit(3)->get()
        : collect();
    $recentMatches = Schema::hasTable('matches')
        ? MatchRecord::with('players.user.playerProfile')->where('status', 'confirmed')->latest()->limit(3)->get()
        : collect();
    $featuredClubs = Schema::hasTable('clubs')
        ? Club::withCount('members')->orderByDesc('members_count')->orderBy('name')->limit(6)->get()
        : collect();
    $featuredTournaments = Schema::hasTable('tournaments')
        ? Tournament::with('club')->orderByRaw("case status when 'published' then 0 when 'draft' then 1 else 2 end")->orderBy('starts_at')->limit(6)->get()
        : collect();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Smashr | Badminton Ratings</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#071a80] font-sans text-white antialiased">
        <header class="border-b border-white/10 bg-white text-[#071a80]">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
                <a href="/" class="flex items-center">
                    <img src="{{ asset('images/smashr-wordmark.png') }}" alt="SmashR" width="128" height="32" class="h-8 w-32 object-contain" style="width: 128px; height: 32px;">
                </a>
                <nav class="hidden items-center gap-8 text-sm font-extrabold uppercase md:flex">
                    <a href="{{ route('rankings') }}" class="hover:text-[#d6a31d]">Rankings</a>
                    <a href="{{ route('matches.index') }}" class="hover:text-[#d6a31d]">Matches</a>
                    <a href="{{ route('clubs.index') }}" class="hover:text-[#d6a31d]">Clubs</a>
                    <a href="{{ route('tournaments.index') }}" class="hover:text-[#d6a31d]">Tournaments</a>
                </nav>
                <div class="hidden items-center gap-4 text-sm font-bold uppercase md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-[#d6a31d]">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-[#d6a31d]">Login</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-[#071a80] px-4 py-2 text-white hover:bg-[#0b2bc1]">Register</a>
                    @endauth
                </div>
                <label for="public-mobile-menu" class="md:hidden">
                    <span class="sr-only">Open menu</span>
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke-width="2.5" stroke-linecap="round" />
                    </svg>
                </label>
            </div>
            <input id="public-mobile-menu" type="checkbox" class="peer sr-only">
            <div class="fixed inset-0 z-50 hidden bg-white text-[#1d3448] peer-checked:block md:hidden">
                <div class="flex h-20 items-center justify-between border-b border-blue-950/10 px-6">
                    <a href="/">
                        <img src="{{ asset('images/smashr-wordmark.png') }}" alt="SmashR" width="136" height="34" class="h-[34px] w-[136px] object-contain" style="width: 136px; height: 34px;">
                    </a>
                    <label for="public-mobile-menu" class="text-blue-950/45">
                        <span class="sr-only">Close menu</span>
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 6l12 12M18 6 6 18" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </label>
                </div>
                <div class="px-6 py-10">
                    <div class="divide-y divide-dashed divide-blue-950/15 border-y border-dashed border-blue-950/15">
                        <a href="{{ route('rankings') }}" class="block py-6 text-2xl font-black uppercase tracking-[.18em]">Rankings</a>
                        <a href="{{ route('matches.index') }}" class="block py-6 text-2xl font-black uppercase tracking-[.18em]">Matches</a>
                        <a href="{{ route('clubs.index') }}" class="block py-6 text-2xl font-black uppercase tracking-[.18em]">Clubs</a>
                        <a href="{{ route('tournaments.index') }}" class="block py-6 text-2xl font-black uppercase tracking-[.18em]">Tournaments</a>
                    </div>
                    <div class="mt-8 grid grid-cols-2 gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-full bg-[#071a80] px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-white">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-blue-950/15 px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-[#071a80]">Login</a>
                            <a href="{{ route('register') }}" class="rounded-full bg-[#071a80] px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-white">Register</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden bg-[#071a80]">
                <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 20% 20%, #ffffff 0 1px, transparent 1px); background-size: 28px 28px;"></div>
                <div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 lg:grid-cols-[1.45fr_.75fr]">
                    <div class="relative min-h-[520px] overflow-hidden rounded-lg bg-cover bg-center shadow-2xl" style="background-image: linear-gradient(90deg, rgba(3,10,52,.9), rgba(3,10,52,.2)), url('https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1600&q=80');">
                        <div class="absolute bottom-0 max-w-3xl p-8 md:p-12">
                            <p class="mb-3 text-xs font-black uppercase tracking-[.3em] text-[#d6a31d]">Badminton rating network</p>
                            <h1 class="text-5xl font-black leading-none md:text-7xl">Know your level. Prove it on court.</h1>
                            <p class="mt-5 max-w-2xl text-lg text-white/80">A DUPR-style platform for badminton players, clubs, and competitive communities with confirmed results and transparent singles and doubles ratings.</p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="{{ route('register') }}" class="rounded-full bg-white px-6 py-3 text-sm font-black uppercase text-[#071a80]">Create profile</a>
                                <a href="{{ route('rankings') }}" class="rounded-full border border-white/40 px-6 py-3 text-sm font-black uppercase text-white">View rankings</a>
                            </div>
                        </div>
                    </div>

                    <aside class="grid gap-5">
                        <div class="rounded-lg bg-white p-6 text-[#071a80] shadow-xl">
                            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Live table</p>
                            <h2 class="mt-2 text-2xl font-black">Top singles</h2>
                            <div class="mt-5 space-y-4">
                                @forelse ($topSingles as $player)
                                    <a href="{{ route('players.show', $player) }}" class="flex items-center justify-between border-b border-blue-950/10 pb-3">
                                        <span class="font-bold">{{ $player->display_name }}</span>
                                        <span class="font-black">{{ $player->singles_rating }}</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-blue-950/60">No confirmed singles matches yet.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="rounded-lg bg-[#020c4d] p-6 shadow-xl">
                            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Club ready</p>
                            <h2 class="mt-2 text-2xl font-black">Singles and doubles ratings from confirmed match results.</h2>
                        </div>
                    </aside>
                </div>
            </section>

            <section id="matches" class="bg-[#f3f6fb] py-14 text-[#06164a]">
                <div class="mx-auto max-w-7xl px-5">
                    <div class="mb-8 flex items-end justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Competition feed</p>
                            <h2 class="text-3xl font-black">Recent match activity</h2>
                        </div>
                        <a href="{{ route('matches.index') }}" class="hidden rounded-full bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white md:inline-flex">View all matches</a>
                    </div>
                    <div class="grid gap-5 md:grid-cols-3">
                        @forelse ($recentMatches as $match)
                            <a href="{{ route('matches.index', ['format' => $match->format, 'status' => $match->status]) }}" class="block rounded-lg bg-white p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#d6a31d] focus:ring-offset-2 focus:ring-offset-[#f3f6fb]">
                                <p class="text-xs font-black uppercase text-[#d6a31d]">{{ $match->format }} | {{ str_replace('_', ' ', $match->status) }}</p>
                                <h3 class="mt-3 text-xl font-black">Side {{ $match->winner_side }} win</h3>
                                <p class="mt-2 text-sm text-blue-950/60">{{ $match->played_at->format('M j, Y') }}</p>
                            </a>
                        @empty
                            <article class="rounded-lg bg-white p-6 shadow-lg md:col-span-3">
                                <h3 class="text-xl font-black">No matches yet</h3>
                                <p class="mt-2 text-blue-950/60">Register and submit the first confirmed match for your community.</p>
                            </article>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="clubs" class="bg-white py-14 text-[#06164a]">
                <div class="mx-auto max-w-7xl px-5">
                    <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Built for clubs</p>
                            <h2 class="text-4xl font-black">Club directory</h2>
                        </div>
                        <a href="{{ route('clubs.index') }}" class="w-fit rounded-full bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">View all clubs</a>
                    </div>
                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @forelse ($featuredClubs as $club)
                            <a href="{{ route('clubs.show', $club) }}" class="rounded-lg border border-blue-950/10 p-6 transition hover:border-[#d6a31d] hover:shadow-lg">
                                <p class="text-xs font-black uppercase tracking-[.22em] text-[#d6a31d]">{{ $club->city ?: $club->state ?: $club->country ?: 'Club' }}</p>
                                <h3 class="mt-3 text-2xl font-black text-[#071a80]">{{ $club->name }}</h3>
                                <p class="mt-3 text-sm text-blue-950/60">{{ $club->description ?: 'A SmashR badminton club.' }}</p>
                                <div class="mt-6 flex items-center justify-between border-t border-blue-950/10 pt-4">
                                    <span class="text-sm font-bold text-blue-950/60">Members</span>
                                    <span class="text-2xl font-black text-[#071a80]">{{ $club->members_count }}</span>
                                </div>
                            </a>
                        @empty
                            <article class="rounded-lg border border-blue-950/10 p-6 md:col-span-2 lg:col-span-3">
                                <h3 class="text-xl font-black text-[#071a80]">No clubs yet</h3>
                                <p class="mt-2 text-blue-950/60">Create a profile and add your club to start building the directory.</p>
                            </article>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="tournaments" class="bg-[#f3f6fb] py-14 text-[#06164a]">
                <div class="mx-auto max-w-7xl px-5">
                    <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Tournament calendar</p>
                            <h2 class="text-4xl font-black">Upcoming and recent tournaments</h2>
                        </div>
                        <a href="{{ route('tournaments.index') }}" class="w-fit rounded-full bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">View all tournaments</a>
                    </div>
                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @forelse ($featuredTournaments as $tournament)
                            <a href="{{ route('tournaments.show', $tournament) }}" class="block rounded-lg bg-white p-6 shadow-lg transition hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#d6a31d] focus:ring-offset-2 focus:ring-offset-[#f3f6fb]">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ $tournament->status }}</p>
                                    <span class="rounded-full bg-[#f3f6fb] px-3 py-1 text-xs font-black uppercase text-[#071a80]">{{ $tournament->starts_at?->format('M j') ?? 'TBA' }}</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-black text-[#071a80]">{{ $tournament->name }}</h3>
                                <p class="mt-3 text-sm text-blue-950/60">{{ collect([$tournament->city, $tournament->state, $tournament->country])->filter()->join(', ') ?: 'Location TBA' }}</p>
                                <div class="mt-6 border-t border-blue-950/10 pt-4">
                                    <p class="text-sm font-bold text-blue-950/60">{{ $tournament->club?->name ?? 'Independent tournament' }}</p>
                                    @if ($tournament->starts_at && $tournament->ends_at)
                                        <p class="mt-1 text-sm text-blue-950/50">{{ $tournament->starts_at->format('M j, Y') }} - {{ $tournament->ends_at->format('M j, Y') }}</p>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <article class="rounded-lg bg-white p-6 shadow-lg md:col-span-2 lg:col-span-3">
                                <h3 class="text-xl font-black text-[#071a80]">No tournaments yet</h3>
                                <p class="mt-2 text-blue-950/60">Publish tournaments from the admin area to show them here.</p>
                            </article>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
