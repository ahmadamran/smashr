<?php

use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Clubs\Models\Club;
use Modules\Players\Models\PlayerProfile;

new #[Layout('layouts.app')] class extends Component
{
    public string $display_name = '';
    public string $country = '';
    public string $state = '';
    public string $city = '';
    public string $preferred_hand = 'right';
    public string $primary_format = 'doubles';
    public string $club_name = '';

    public function mount(): void
    {
        $profile = auth()->user()->playerProfile;

        $this->display_name = $profile?->display_name ?? auth()->user()->name;
        $this->country = $profile?->country ?? '';
        $this->state = $profile?->state ?? '';
        $this->city = $profile?->city ?? '';
        $this->preferred_hand = $profile?->preferred_hand ?? 'right';
        $this->primary_format = $profile?->primary_format ?? 'doubles';
        $this->club_name = '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'preferred_hand' => ['required', 'in:right,left'],
            'primary_format' => ['required', 'in:singles,doubles,mixed'],
            'club_name' => ['nullable', 'string', 'max:120'],
        ]);

        $baseSlug = Str::slug($validated['display_name']);
        $profile = PlayerProfile::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'display_name' => $validated['display_name'],
                'country' => $validated['country'],
                'state' => $validated['state'],
                'city' => $validated['city'],
                'preferred_hand' => $validated['preferred_hand'],
                'primary_format' => $validated['primary_format'],
                'slug' => $this->uniqueSlug($baseSlug ?: 'player'),
            ],
        );

        if ($validated['club_name'] !== '') {
            $club = Club::firstOrCreate(
                ['slug' => Str::slug($validated['club_name'])],
                [
                    'name' => $validated['club_name'],
                    'country' => $validated['country'],
                    'state' => $validated['state'],
                    'city' => $validated['city'],
                    'description' => 'Community badminton club on Smashr.',
                ],
            );

            auth()->user()->clubs()->syncWithoutDetaching([$club->id]);
        }

        session()->flash('status', 'Player profile saved.');
        $this->redirect(route('players.show', $profile), navigate: true);
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $count = 2;

        while (PlayerProfile::where('slug', $slug)->where('user_id', '!=', auth()->id())->exists()) {
            $slug = $baseSlug.'-'.$count++;
        }

        return $slug;
    }
}; ?>

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-lg bg-white p-8 shadow-lg">
        <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Badminton identity</p>
        <h1 class="mt-2 text-3xl font-black text-brand-blue">Player profile</h1>
        @if (auth()->user()->clubs->isNotEmpty())
            <p class="mt-3 text-sm font-bold text-brand-ink/55">Current clubs: {{ auth()->user()->clubs->pluck('name')->join(', ') }}</p>
        @endif

        <form wire:submit="save" class="mt-8 grid gap-5">
            <div>
                <x-input-label for="display_name" value="Display name" />
                <x-text-input wire:model="display_name" id="display_name" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                <div><x-input-label for="country" value="Country" /><x-text-input wire:model="country" id="country" class="mt-1 block w-full" /></div>
                <div><x-input-label for="state" value="State" /><x-text-input wire:model="state" id="state" class="mt-1 block w-full" /></div>
                <div><x-input-label for="city" value="City" /><x-text-input wire:model="city" id="city" class="mt-1 block w-full" /></div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <x-input-label for="preferred_hand" value="Preferred hand" />
                    <select wire:model="preferred_hand" id="preferred_hand" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="right">Right</option>
                        <option value="left">Left</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="primary_format" value="Primary format" />
                    <select wire:model="primary_format" id="primary_format" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="doubles">Doubles</option>
                        <option value="singles">Singles</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
            </div>

            <div>
                <x-input-label for="club_name" value="Add club or school optional" />
                <x-text-input wire:model="club_name" id="club_name" class="mt-1 block w-full" />
            </div>

            <button class="rounded-full bg-brand-blue px-6 py-3 text-sm font-black uppercase text-white">Save profile</button>
        </form>
    </div>
</div>
