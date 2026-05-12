@php
    use Modules\Matches\Models\MatchRecord;
    use Modules\Ratings\Models\RatingEvent;

    $profile = auth()->user()->playerProfile;
    $pendingMatches = MatchRecord::where('status', 'pending_confirmation')
        ->whereHas('players', fn ($query) => $query->where('user_id', auth()->id())->whereNull('confirmed_at'))
        ->latest()
        ->get();
    $recentEvents = RatingEvent::with('match.players.user.playerProfile', 'algorithm')
        ->where('user_id', auth()->id())
        ->latest()
        ->limit(6)
        ->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Player dashboard</p>
                <h1 class="text-3xl font-black text-brand-blue">{{ $profile?->display_name ?? auth()->user()->name }}</h1>
            </div>
            <a href="{{ route('matches.create') }}" wire:navigate class="rounded-full bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Submit result</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @unless ($profile)
            <div class="mb-8 rounded-lg bg-brand-blue p-6 text-white">
                <h2 class="text-2xl font-black">Finish your badminton profile</h2>
                <p class="mt-2 text-white/70">Add your location and preferred format so you can enter rankings.</p>
                <a href="{{ route('profile.player') }}" wire:navigate class="mt-5 inline-flex rounded-full bg-white px-5 py-3 text-xs font-black uppercase text-brand-blue">Complete profile</a>
            </div>
        @endunless

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Singles</p>
                <p class="mt-3 text-5xl font-black text-brand-blue">{{ $profile?->singles_rating ?? '3.500' }}</p>
                <p class="mt-2 text-sm text-brand-ink/60">{{ $profile?->singles_matches ?? 0 }} confirmed matches</p>
            </section>
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Doubles</p>
                <p class="mt-3 text-5xl font-black text-brand-blue">{{ $profile?->doubles_rating ?? '3.500' }}</p>
                <p class="mt-2 text-sm text-brand-ink/60">{{ $profile?->doubles_matches ?? 0 }} confirmed matches</p>
            </section>
            <section class="rounded-lg bg-brand-blue p-6 text-white shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-mist">Profile</p>
                <p class="mt-3 text-xl font-black">{{ $profile?->city ?: 'Location missing' }}</p>
                <p class="mt-2 text-sm text-white/70">{{ $profile?->primary_format ? ucfirst($profile->primary_format).' player' : 'Set your primary format' }}</p>
                <a href="{{ route('profile.player') }}" wire:navigate class="mt-5 inline-flex text-sm font-black uppercase text-brand-mist hover:text-white">Edit profile</a>
            </section>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-2xl font-black text-brand-blue">Pending confirmations</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($pendingMatches as $match)
                        <a href="{{ route('matches.confirm', $match) }}" wire:navigate class="block rounded-md border border-brand-ink/10 p-4 hover:border-brand-blue">
                            <span class="text-xs font-black uppercase text-brand-green">{{ $match->format }}</span>
                            <span class="block font-bold">Side {{ $match->winner_side }} win on {{ $match->played_at->format('M j, Y') }}</span>
                        </a>
                    @empty
                        <p class="text-brand-ink/60">No match confirmations waiting.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-2xl font-black text-brand-blue">Rating history</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($recentEvents as $event)
                        @php
                            $match = $event->match;
                            $playerSide = $match?->players->firstWhere('user_id', auth()->id())?->side;
                            $opponents = $match?->players
                                ->where('side', '!=', $playerSide)
                                ->map(fn ($player) => $player->user->playerProfile?->display_name ?? $player->user->name)
                                ->join(' / ');
                            $score = collect($match?->score ?? [])->map(fn ($game) => ($game['a'] ?? 0).'-'.($game['b'] ?? 0))->join(', ');
                        @endphp
                        <div class="border-b border-brand-ink/10 pb-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-black text-brand-blue">{{ ucfirst($event->format) }} vs {{ $opponents ?: 'TBA' }}</p>
                                    <p class="text-sm text-brand-ink/60">{{ $match?->played_at?->format('M j, Y') }} | Score {{ $score ?: 'not set' }}</p>
                                </div>
                                <p class="font-black {{ $event->delta >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $event->delta >= 0 ? '+' : '' }}{{ $event->delta }}</p>
                            </div>
                            <div class="mt-3 grid gap-2 rounded-md bg-brand-surface p-3 text-sm font-bold text-brand-ink/70 sm:grid-cols-3">
                                <p>Before <span class="block text-lg font-black text-brand-blue">{{ $event->rating_before }}</span></p>
                                <p>After <span class="block text-lg font-black text-brand-blue">{{ $event->rating_after }}</span></p>
                                <p>Algorithm <span class="block text-lg font-black text-brand-blue">{{ $event->algorithm?->version ?? 'v1' }}</span></p>
                            </div>
                        </div>
                    @empty
                        <p class="text-brand-ink/60">Confirmed matches will create rating events here.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
