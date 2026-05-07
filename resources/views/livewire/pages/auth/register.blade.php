<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Clubs\Models\Club;
use Modules\Players\Models\PlayerProfile;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $country = '';
    public string $state = '';
    public string $city = '';
    public string $preferred_hand = 'right';
    public string $primary_format = 'doubles';
    public string $club_name = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'preferred_hand' => ['required', 'in:right,left'],
            'primary_format' => ['required', 'in:singles,doubles'],
            'club_name' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ];

        event(new Registered($user = User::create($userData)));

        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'display_name' => $validated['name'],
            'slug' => $this->uniqueSlug(Str::slug($validated['name']) ?: 'player'),
            'country' => $validated['country'],
            'state' => $validated['state'],
            'city' => $validated['city'],
            'preferred_hand' => $validated['preferred_hand'],
            'primary_format' => $validated['primary_format'],
        ]);

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

            $user->clubs()->attach($club);
        }

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $count = 2;

        while (PlayerProfile::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count++;
        }

        return $slug;
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="country" :value="__('Country')" />
                <x-text-input wire:model="country" id="country" class="block mt-1 w-full" type="text" name="country" />
            </div>
            <div>
                <x-input-label for="state" :value="__('State')" />
                <x-text-input wire:model="state" id="state" class="block mt-1 w-full" type="text" name="state" />
            </div>
            <div>
                <x-input-label for="city" :value="__('City')" />
                <x-text-input wire:model="city" id="city" class="block mt-1 w-full" type="text" name="city" />
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="preferred_hand" :value="__('Preferred hand')" />
                <select wire:model="preferred_hand" id="preferred_hand" class="block mt-1 w-full rounded-md border-gray-300">
                    <option value="right">Right</option>
                    <option value="left">Left</option>
                </select>
            </div>
            <div>
                <x-input-label for="primary_format" :value="__('Primary format')" />
                <select wire:model="primary_format" id="primary_format" class="block mt-1 w-full rounded-md border-gray-300">
                    <option value="doubles">Doubles</option>
                    <option value="singles">Singles</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="club_name" :value="__('Club name optional')" />
            <x-text-input wire:model="club_name" id="club_name" class="block mt-1 w-full" type="text" name="club_name" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
