<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Tournaments\Models\Tournament;

new #[Layout('layouts.app')] class extends Component
{
    public string $format = 'singles';
    public string $played_at = '';
    public string $winner_side = 'A';
    public string $club_id = '';
    public string $tournament_id = '';
    public array $score = [['a' => 21, 'b' => 15], ['a' => 21, 'b' => 18]];
    public string $side_a_1 = '';
    public string $side_a_2 = '';
    public string $side_b_1 = '';
    public string $side_b_2 = '';

    public function mount(): void
    {
        $this->played_at = now()->toDateString();
        $this->side_a_1 = auth()->user()->email;
    }

    public function with(): array
    {
        return [
            'clubs' => Club::orderBy('name')->get(['id', 'name']),
            'tournaments' => Tournament::where('status', 'published')->orderBy('starts_at')->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'format' => ['required', 'in:singles,doubles'],
            'played_at' => ['required', 'date', 'before_or_equal:today'],
            'winner_side' => ['required', 'in:A,B'],
            'club_id' => ['nullable', 'exists:clubs,id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'score' => ['required', 'array', 'min:1'],
            'score.*.a' => ['required', 'integer', 'min:0', 'max:40'],
            'score.*.b' => ['required', 'integer', 'min:0', 'max:40'],
            'side_a_1' => ['required', 'email', 'exists:users,email'],
            'side_b_1' => ['required', 'email', 'different:side_a_1', 'exists:users,email'],
            'side_a_2' => [$this->format === 'doubles' ? 'required' : 'nullable', 'email', 'different:side_a_1', 'different:side_b_1', 'exists:users,email'],
            'side_b_2' => [$this->format === 'doubles' ? 'required' : 'nullable', 'email', 'different:side_a_1', 'different:side_b_1', 'different:side_a_2', 'exists:users,email'],
        ]);

        $users = collect([
            ['email' => $validated['side_a_1'], 'side' => 'A', 'position' => 1],
            ['email' => $validated['side_b_1'], 'side' => 'B', 'position' => 1],
            ['email' => $validated['side_a_2'] ?: null, 'side' => 'A', 'position' => 2],
            ['email' => $validated['side_b_2'] ?: null, 'side' => 'B', 'position' => 2],
        ])->filter(fn ($player) => $player['email']);

        if ($users->pluck('email')->unique()->count() !== $users->count()) {
            $this->addError('side_b_2', 'Each player can only appear once.');
            return;
        }

        $match = MatchRecord::create([
            'format' => $validated['format'],
            'submitted_by' => auth()->id(),
            'club_id' => $validated['club_id'] ?: null,
            'tournament_id' => $validated['tournament_id'] ?: null,
            'status' => 'pending_confirmation',
            'played_at' => $validated['played_at'],
            'score' => $validated['score'],
            'winner_side' => $validated['winner_side'],
        ]);

        foreach ($users as $player) {
            $user = User::where('email', $player['email'])->firstOrFail();
            $match->players()->create([
                'user_id' => $user->id,
                'side' => $player['side'],
                'position' => $player['position'],
                'confirmed_at' => $user->is(auth()->user()) ? now() : null,
            ]);
        }

        session()->flash('status', 'Match submitted. Ratings update after all players confirm.');
        $this->redirect(route('matches.confirm', $match), navigate: true);
    }
}; ?>

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-lg bg-white p-8 shadow-lg">
        <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Submit result</p>
        <h1 class="mt-2 text-3xl font-black text-brand-blue">Match details</h1>

        <form wire:submit="submit" class="mt-8 grid gap-6">
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <x-input-label for="format" value="Format" />
                    <select wire:model.live="format" id="format" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="singles">Singles</option>
                        <option value="doubles">Doubles</option>
                    </select>
                </div>
                <div><x-input-label for="played_at" value="Played at" /><x-text-input wire:model="played_at" id="played_at" type="date" class="mt-1 block w-full" /></div>
                <div>
                    <x-input-label for="winner_side" value="Winner" />
                    <select wire:model="winner_side" id="winner_side" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="A">Side A</option>
                        <option value="B">Side B</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <x-input-label for="club_id" value="Club context" />
                    <select wire:model="club_id" id="club_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Player match</option>
                        @foreach ($clubs as $club)
                            <option value="{{ $club->id }}">{{ $club->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="tournament_id" value="Tournament context" />
                    <select wire:model="tournament_id" id="tournament_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">No tournament</option>
                        @foreach ($tournaments as $tournament)
                            <option value="{{ $tournament->id }}">{{ $tournament->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <section class="rounded-md border border-brand-ink/10 p-5">
                    <h2 class="font-black text-brand-blue">Side A</h2>
                    <x-text-input wire:model="side_a_1" class="mt-4 block w-full" placeholder="Player email" />
                    @if ($format === 'doubles') <x-text-input wire:model="side_a_2" class="mt-3 block w-full" placeholder="Partner email" /> @endif
                </section>
                <section class="rounded-md border border-brand-ink/10 p-5">
                    <h2 class="font-black text-brand-blue">Side B</h2>
                    <x-text-input wire:model="side_b_1" class="mt-4 block w-full" placeholder="Opponent email" />
                    @if ($format === 'doubles') <x-text-input wire:model="side_b_2" class="mt-3 block w-full" placeholder="Opponent partner email" /> @endif
                </section>
            </div>
            <x-input-error :messages="$errors->all()" class="mt-2" />

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ([0, 1, 2] as $index)
                    <div class="rounded-md bg-brand-surface p-4">
                        <p class="text-xs font-black uppercase text-brand-ink/50">Game {{ $index + 1 }}</p>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <x-text-input wire:model="score.{{ $index }}.a" type="number" min="0" max="40" />
                            <x-text-input wire:model="score.{{ $index }}.b" type="number" min="0" max="40" />
                        </div>
                    </div>
                @endforeach
            </div>

            <button class="rounded-full bg-brand-blue px-6 py-3 text-sm font-black uppercase text-white">Submit for confirmation</button>
        </form>
    </div>
</div>
