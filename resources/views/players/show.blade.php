@php
    use Modules\Ratings\Models\RatingEvent;

    $ratingEvents = RatingEvent::with('match.players.user.playerProfile', 'algorithm')
        ->where('user_id', $player->user_id)
        ->latest()
        ->limit(10)
        ->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Player profile</p>
                <h1 class="break-words text-3xl font-black text-brand-blue sm:text-4xl">{{ $player->display_name }}</h1>
            </div>
            <p class="font-bold text-brand-ink/60">{{ collect([$player->city, $player->state, $player->country])->filter()->join(', ') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        <section class="rounded-lg bg-brand-blue p-8 text-white shadow-lg lg:col-span-1">
            <p class="text-xs font-black uppercase tracking-[.25em] text-brand-mist">Primary format</p>
            <h2 class="mt-3 text-3xl font-black">{{ ucfirst($player->primary_format) }}</h2>
            <p class="mt-6 text-white/70">{{ ucfirst($player->preferred_hand) }} handed</p>
            <p class="text-white/70">{{ $player->user->clubs->pluck('name')->join(', ') ?: 'Independent player' }}</p>
        </section>
        <section class="grid gap-6 md:grid-cols-2 lg:col-span-2">
            <div class="rounded-lg bg-white p-8 shadow-lg">
                <p class="text-xs font-black uppercase text-brand-green">Singles rating</p>
                <p class="mt-3 text-6xl font-black text-brand-blue">{{ $player->singles_rating }}</p>
                <p class="mt-2 text-brand-ink/60">{{ $player->singles_matches }} confirmed matches</p>
            </div>
            <div class="rounded-lg bg-white p-8 shadow-lg">
                <p class="text-xs font-black uppercase text-brand-green">Doubles rating</p>
                <p class="mt-3 text-6xl font-black text-brand-blue">{{ $player->doubles_rating }}</p>
                <p class="mt-2 text-brand-ink/60">{{ $player->doubles_matches }} confirmed matches</p>
            </div>
        </section>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-2xl font-black text-brand-blue">Rating history</h2>
            <div class="mt-5 grid gap-4">
                @forelse ($ratingEvents as $event)
                    @php
                        $match = $event->match;
                        $playerSide = $match?->players->firstWhere('user_id', $player->user_id)?->side;
                        $opponents = $match?->players
                            ->where('side', '!=', $playerSide)
                            ->map(fn ($matchPlayer) => $matchPlayer->user->playerProfile?->display_name ?? $matchPlayer->user->name)
                            ->join(' / ');
                        $score = collect($match?->score ?? [])->map(fn ($game) => ($game['a'] ?? 0).'-'.($game['b'] ?? 0))->join(', ');
                    @endphp
                    <article class="rounded-md border border-brand-ink/10 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-black text-brand-blue">{{ ucfirst($event->format) }} vs {{ $opponents ?: 'TBA' }}</p>
                                <p class="text-sm text-brand-ink/60">{{ $match?->played_at?->format('M j, Y') }} | Score {{ $score ?: 'not set' }}</p>
                            </div>
                            <p class="font-black {{ $event->delta >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $event->delta >= 0 ? '+' : '' }}{{ $event->delta }}</p>
                        </div>
                        <div class="mt-3 grid gap-2 bg-brand-surface p-3 text-sm font-bold text-brand-ink/70 sm:grid-cols-3">
                            <p>Before <span class="block text-lg font-black text-brand-blue">{{ $event->rating_before }}</span></p>
                            <p>After <span class="block text-lg font-black text-brand-blue">{{ $event->rating_after }}</span></p>
                            <p>Algorithm <span class="block text-lg font-black text-brand-blue">{{ $event->algorithm?->version ?? 'v1' }}</span></p>
                        </div>
                    </article>
                @empty
                    <p class="text-brand-ink/60">No rating history yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
