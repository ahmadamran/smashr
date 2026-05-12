<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Matches\Models\MatchRecord;
use Modules\Ratings\Services\RatingService;

new #[Layout('layouts.app')] class extends Component
{
    public MatchRecord $match;

    public function mount(int|string $match): void
    {
        $this->match = MatchRecord::with('players.user.playerProfile')->findOrFail($match);
    }

    public function confirm(RatingService $ratings): void
    {
        $ratings->confirmForUser($this->match, auth()->id());
        session()->flash('status', 'Match confirmed.');
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function dispute(RatingService $ratings): void
    {
        $ratings->disputeForUser($this->match, auth()->id());
        session()->flash('status', 'Match disputed.');
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-lg bg-white p-8 shadow-lg">
        <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">{{ $match->format }} match</p>
        <h1 class="mt-2 text-3xl font-black text-brand-blue">Confirm result</h1>
        <p class="mt-2 text-brand-ink/60">Played {{ $match->played_at->format('M j, Y') }}. Winner: Side {{ $match->winner_side }}.</p>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach (['A', 'B'] as $side)
                <section class="rounded-md border border-brand-ink/10 p-5">
                    <h2 class="font-black text-brand-blue">Side {{ $side }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($match->players->where('side', $side)->sortBy('position') as $player)
                            <div class="flex items-center justify-between">
                                <span>{{ $player->user->playerProfile?->display_name ?? $player->user->name }}</span>
                                <span class="text-xs font-black uppercase {{ $player->confirmed_at ? 'text-green-600' : 'text-brand-ink/40' }}">{{ $player->confirmed_at ? 'Confirmed' : 'Waiting' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-8 rounded-md bg-brand-surface p-5">
            <h2 class="font-black text-brand-blue">Score</h2>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach ($match->score as $game)
                    <span class="rounded-full bg-white px-4 py-2 font-black">A {{ $game['a'] }} - {{ $game['b'] }} B</span>
                @endforeach
            </div>
        </div>

        @if ($match->status === 'pending_confirmation' && $match->players->contains('user_id', auth()->id()))
            <div class="mt-8 flex flex-wrap gap-3">
                <button wire:click="confirm" class="rounded-full bg-brand-blue px-6 py-3 text-sm font-black uppercase text-white">Confirm result</button>
                <button wire:click="dispute" class="rounded-full border border-red-600 px-6 py-3 text-sm font-black uppercase text-red-600">Dispute</button>
            </div>
        @else
            <p class="mt-8 font-bold text-brand-ink/60">Status: {{ str_replace('_', ' ', $match->status) }}</p>
        @endif
    </div>
</div>
