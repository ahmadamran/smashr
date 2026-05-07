@php
    use Modules\Matches\Models\MatchRecord;
    use Modules\Ratings\Models\RatingEvent;

    $profile = auth()->user()->playerProfile;
    $pendingMatches = MatchRecord::where('status', 'pending_confirmation')
        ->whereHas('players', fn ($query) => $query->where('user_id', auth()->id())->whereNull('confirmed_at'))
        ->latest()
        ->get();
    $recentEvents = RatingEvent::where('user_id', auth()->id())->latest()->limit(6)->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Player dashboard</p>
                <h1 class="text-3xl font-black text-[#071a80]">{{ $profile?->display_name ?? auth()->user()->name }}</h1>
            </div>
            <a href="{{ route('matches.create') }}" wire:navigate class="rounded-full bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">Submit match</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @unless ($profile)
            <div class="mb-8 rounded-lg bg-[#071a80] p-6 text-white">
                <h2 class="text-2xl font-black">Finish your badminton profile</h2>
                <p class="mt-2 text-white/70">Add your location and preferred format so you can enter rankings.</p>
                <a href="{{ route('profile.player') }}" wire:navigate class="mt-5 inline-flex rounded-full bg-white px-5 py-3 text-xs font-black uppercase text-[#071a80]">Complete profile</a>
            </div>
        @endunless

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Singles</p>
                <p class="mt-3 text-5xl font-black text-[#071a80]">{{ $profile?->singles_rating ?? '3.500' }}</p>
                <p class="mt-2 text-sm text-blue-950/60">{{ $profile?->singles_matches ?? 0 }} confirmed matches</p>
            </section>
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Doubles</p>
                <p class="mt-3 text-5xl font-black text-[#071a80]">{{ $profile?->doubles_rating ?? '3.500' }}</p>
                <p class="mt-2 text-sm text-blue-950/60">{{ $profile?->doubles_matches ?? 0 }} confirmed matches</p>
            </section>
            <section class="rounded-lg bg-[#071a80] p-6 text-white shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Profile</p>
                <p class="mt-3 text-xl font-black">{{ $profile?->city ?: 'Location missing' }}</p>
                <p class="mt-2 text-sm text-white/70">{{ $profile?->primary_format ? ucfirst($profile->primary_format).' player' : 'Set your primary format' }}</p>
                <a href="{{ route('profile.player') }}" wire:navigate class="mt-5 inline-flex text-sm font-black uppercase text-[#d6a31d]">Edit profile</a>
            </section>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-2xl font-black text-[#071a80]">Pending confirmations</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($pendingMatches as $match)
                        <a href="{{ route('matches.confirm', $match) }}" wire:navigate class="block rounded-md border border-blue-950/10 p-4 hover:border-[#071a80]">
                            <span class="text-xs font-black uppercase text-[#d6a31d]">{{ $match->format }}</span>
                            <span class="block font-bold">Side {{ $match->winner_side }} win on {{ $match->played_at->format('M j, Y') }}</span>
                        </a>
                    @empty
                        <p class="text-blue-950/60">No match confirmations waiting.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-2xl font-black text-[#071a80]">Rating history</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($recentEvents as $event)
                        <div class="flex items-center justify-between border-b border-blue-950/10 pb-3">
                            <div>
                                <p class="font-bold">{{ ucfirst($event->format) }}</p>
                                <p class="text-sm text-blue-950/60">{{ $event->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="font-black {{ $event->delta >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $event->delta >= 0 ? '+' : '' }}{{ $event->delta }}</p>
                        </div>
                    @empty
                        <p class="text-blue-950/60">Confirmed matches will create rating events here.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
