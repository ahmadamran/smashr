@php
    use Modules\Players\Models\PlayerProfile;
    use Modules\Matches\Models\MatchRecord;
    use Illuminate\Support\Facades\Schema;

    $topSingles = Schema::hasTable('player_profiles')
        ? PlayerProfile::where('singles_matches', '>', 0)->orderByDesc('singles_rating')->limit(3)->get()
        : collect();
    $topDoubles = Schema::hasTable('player_profiles')
        ? PlayerProfile::where('doubles_matches', '>', 0)->orderByDesc('doubles_rating')->limit(3)->get()
        : collect();
    $recentMatches = Schema::hasTable('matches')
        ? MatchRecord::with('players.user.playerProfile')->latest()->limit(3)->get()
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
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('images/smashr-logo.jpg') }}" alt="SmashR logo" width="48" height="48" class="h-12 w-12 rounded-md bg-black object-cover" style="width: 48px; height: 48px;">
                    <span class="text-xl font-black uppercase tracking-wide">SmashR</span>
                </a>
                <nav class="hidden items-center gap-8 text-sm font-extrabold uppercase md:flex">
                    <a href="{{ route('rankings') }}" class="hover:text-[#d6a31d]">Rankings</a>
                    <a href="#matches" class="hover:text-[#d6a31d]">Matches</a>
                    <a href="#clubs" class="hover:text-[#d6a31d]">Clubs</a>
                </nav>
                <div class="flex items-center gap-4 text-sm font-bold uppercase">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-[#d6a31d]">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-[#d6a31d]">Login</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-[#071a80] px-4 py-2 text-white hover:bg-[#0b2bc1]">Register</a>
                    @endauth
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
                        <a href="{{ route('matches.create') }}" class="hidden rounded-full bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white md:inline-flex">Submit match</a>
                    </div>
                    <div class="grid gap-5 md:grid-cols-3">
                        @forelse ($recentMatches as $match)
                            <article class="rounded-lg bg-white p-6 shadow-lg">
                                <p class="text-xs font-black uppercase text-[#d6a31d]">{{ $match->format }} | {{ str_replace('_', ' ', $match->status) }}</p>
                                <h3 class="mt-3 text-xl font-black">Side {{ $match->winner_side }} win</h3>
                                <p class="mt-2 text-sm text-blue-950/60">{{ $match->played_at->format('M j, Y') }}</p>
                            </article>
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
                <div class="mx-auto grid max-w-7xl gap-8 px-5 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Built for clubs</p>
                        <h2 class="text-4xl font-black">A leaderboard home for every badminton hall.</h2>
                    </div>
                    <div class="grid gap-4">
                        <div class="rounded-lg border border-blue-950/10 p-6">
                            <h3 class="font-black">Confirmed results</h3>
                            <p class="mt-2 text-blue-950/60">Players submit matches and opponents confirm before ratings move.</p>
                        </div>
                        <div class="rounded-lg border border-blue-950/10 p-6">
                            <h3 class="font-black">Separate formats</h3>
                            <p class="mt-2 text-blue-950/60">Singles and doubles ratings stay separate, because badminton demands both.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
