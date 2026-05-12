<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;

new #[Layout('layouts.app')] class extends Component
{
    public MatchRecord $match;
    public array $liveScore = [];

    public function mount(string $token, MatchScoreService $scores): void
    {
        $this->match = MatchRecord::with('tournament.club', 'tournamentCategory', 'players.user.playerProfile')
            ->where('score_sheet_token', $token)
            ->firstOrFail();
        $this->liveScore = $scores->liveScore($this->match);
    }

    public function addPoint(string $side, MatchScoreService $scores): void
    {
        $scores->addPoint($this->match, $side);
        $this->reload($scores);
    }

    public function undo(MatchScoreService $scores): void
    {
        $scores->undoPoint($this->match);
        $this->reload($scores);
    }

    public function endGame(MatchScoreService $scores): void
    {
        $scores->endCurrentGame($this->match);
        $this->reload($scores);
    }

    public function submitScore(MatchScoreService $scores): void
    {
        $scores->submitLiveScore($this->match);
        $this->reload($scores);
        session()->flash('status', 'Scoresheet submitted for organizer review.');
    }

    private function reload(MatchScoreService $scores): void
    {
        $this->match = $this->match->fresh('tournament.club', 'tournamentCategory', 'players.user.playerProfile');
        $this->liveScore = $scores->liveScore($this->match);
    }
}; ?>

@php
    $sideA = $match->players->where('side', 'A')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
    $sideB = $match->players->where('side', 'B')->sortBy('position')->map(fn ($p) => $p->user->playerProfile?->display_name ?? $p->user->name)->join(' / ') ?: 'TBA';
    $current = $liveScore['current'] ?? ['a' => 0, 'b' => 0];
    $games = collect($liveScore['games'] ?? []);
    $sideAWins = $games->filter(fn ($game) => (int) ($game['a'] ?? 0) > (int) ($game['b'] ?? 0))->count();
    $sideBWins = $games->filter(fn ($game) => (int) ($game['b'] ?? 0) > (int) ($game['a'] ?? 0))->count();
    $locked = in_array($match->live_status, ['submitted', 'approved'], true) || $match->status === 'confirmed';
@endphp

<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-lg bg-white p-5 shadow-lg sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-green">{{ $match->tournament?->name ?? 'Tournament match' }}</p>
                <h1 class="mt-2 text-2xl font-black text-brand-blue sm:text-3xl">Umpire scoresheet</h1>
                <p class="mt-2 text-sm font-bold text-brand-ink/55">{{ $match->tournamentCategory?->name ?? 'Match' }} | {{ $match->draw_group ?: 'Round '.$match->draw_round }}</p>
            </div>
            <span class="rounded-full bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">{{ str_replace('_', ' ', $match->live_status) }}</span>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-md bg-green-50 p-4 text-sm font-bold text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-5 rounded-md bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="mt-6 grid gap-3">
            <section class="rounded-lg border border-brand-ink/10 p-4">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-ink/45">Side A</p>
                <p class="mt-2 text-lg font-black text-brand-blue">{{ $sideA }}</p>
            </section>
            <section class="rounded-lg border border-brand-ink/10 p-4">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-ink/45">Side B</p>
                <p class="mt-2 text-lg font-black text-brand-blue">{{ $sideB }}</p>
            </section>
        </div>

        <div class="mt-6 rounded-lg bg-brand-blue p-5 text-white">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-mist">Game {{ $liveScore['current_game'] ?? 1 }}</p>
                    <p class="mt-2 text-sm font-bold text-white/60">Games: A {{ $sideAWins }} - {{ $sideBWins }} B</p>
                </div>
                @if ($match->live_status === 'live')
                    <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-black uppercase text-white">Live</span>
                @endif
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3 text-center">
                <div class="rounded-lg bg-white/10 p-5">
                    <p class="text-xs font-black uppercase text-white/55">Side A</p>
                    <p class="mt-2 text-6xl font-black">{{ (int) ($current['a'] ?? 0) }}</p>
                </div>
                <div class="rounded-lg bg-white/10 p-5">
                    <p class="text-xs font-black uppercase text-white/55">Side B</p>
                    <p class="mt-2 text-6xl font-black">{{ (int) ($current['b'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        @if ($games->isNotEmpty())
            <div class="mt-5 grid gap-2 sm:grid-cols-3">
                @foreach ($games as $index => $game)
                    <div class="rounded-md bg-brand-surface p-3">
                        <p class="text-[11px] font-black uppercase text-brand-ink/45">Game {{ $index + 1 }}</p>
                        <p class="mt-1 text-2xl font-black text-brand-blue">{{ (int) $game['a'] }} - {{ (int) $game['b'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! $locked)
            <div class="mt-6 grid grid-cols-2 gap-3">
                <button wire:click="addPoint('A')" class="min-h-28 rounded-lg bg-brand-blue px-4 py-6 text-xl font-black uppercase text-white">+1 Side A</button>
                <button wire:click="addPoint('B')" class="min-h-28 rounded-lg bg-brand-green px-4 py-6 text-xl font-black uppercase text-white">+1 Side B</button>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <button wire:click="undo" class="rounded-md border border-brand-ink/10 px-4 py-3 text-sm font-black uppercase text-brand-blue">Undo point</button>
                <button wire:click="endGame" class="rounded-md border border-brand-ink/10 px-4 py-3 text-sm font-black uppercase text-brand-blue">End game</button>
                <button wire:click="submitScore" class="rounded-md bg-green-700 px-4 py-3 text-sm font-black uppercase text-white">Submit final</button>
            </div>
        @else
            <div class="mt-6 rounded-md bg-brand-surface p-4 text-sm font-bold text-brand-ink/65">
                This score sheet has been submitted. The organizer can approve it from the tournament matches page.
            </div>
        @endif
    </div>
</div>
