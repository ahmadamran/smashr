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
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone_number = '';
    public string $gender = '';
    public string $birthdate = '';
    public string $country = '';
    public string $state = '';
    public string $city = '';
    public string $postal_code = '';
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
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_number' => ['required', 'string', 'max:40'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'birthdate' => ['required', 'date', 'before:today'],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'preferred_hand' => ['required', 'in:right,left'],
            'primary_format' => ['required', 'in:singles,doubles'],
            'club_name' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);
        $fullName = trim($validated['first_name'].' '.$validated['last_name']);

        $userData = [
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ];

        event(new Registered($user = User::create($userData)));

        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'display_name' => $fullName,
            'slug' => $this->uniqueSlug(Str::slug($fullName) ?: 'player'),
            'phone_number' => $validated['phone_number'],
            'gender' => $validated['gender'],
            'birthdate' => $validated['birthdate'],
            'country' => $validated['country'],
            'state' => $validated['state'],
            'city' => $validated['city'],
            'postal_code' => $validated['postal_code'],
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

<div class="grid gap-8 lg:grid-cols-[.85fr_1.15fr]">
    <aside class="rounded-lg bg-[#071a80] p-7 text-white">
        <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Create account</p>
        <h1 class="mt-4 text-4xl font-black leading-tight">Join SmashR and start building your rating.</h1>
        <div class="mt-8 space-y-4 text-sm font-bold text-white/75">
            <p class="rounded-md bg-white/10 p-4">1. Create your login.</p>
            <p class="rounded-md bg-white/10 p-4">2. Add your badminton profile and location.</p>
            <p class="rounded-md bg-white/10 p-4">3. Submit matches and track ratings.</p>
        </div>
    </aside>

    <form wire:submit="register" class="grid gap-6">
        <section>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Account details</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="first_name" :value="__('First Name')" />
                    <x-text-input wire:model="first_name" id="first_name" class="mt-2 block w-full" type="text" name="first_name" required autofocus autocomplete="given-name" placeholder="First name" />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="last_name" :value="__('Last Name')" />
                    <x-text-input wire:model="last_name" id="last_name" class="mt-2 block w-full" type="text" name="last_name" required autocomplete="family-name" placeholder="Last name" />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email" :value="__('Email address')" />
                    <x-text-input wire:model="email" id="email" class="mt-2 block w-full" type="email" name="email" required autocomplete="username" placeholder="you@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="phone_number" :value="__('Phone Number')" />
                    <x-text-input wire:model="phone_number" id="phone_number" class="mt-2 block w-full" type="tel" name="phone_number" required autocomplete="tel" placeholder="+60..." />
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                </div>
            </div>
        </section>

        <section>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Player details</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="gender" :value="__('Gender')" />
                    <select wire:model="gender" id="gender" class="mt-2 block w-full rounded-md border-gray-300" required>
                        <option value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                        <option value="prefer_not_to_say">Prefer not to say</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="birthdate" :value="__('Birthdate')" />
                    <x-text-input wire:model="birthdate" id="birthdate" class="mt-2 block w-full" type="date" name="birthdate" required autocomplete="bday" />
                    <x-input-error :messages="$errors->get('birthdate')" class="mt-2" />
                </div>
            </div>
        </section>

        <section>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Location</p>
            <p class="mt-2 text-sm font-bold text-blue-950/50">Use your badminton home base, for example: Kuala Lumpur, Malaysia.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-4">
                <div>
                    <x-input-label for="country" :value="__('Country')" />
                    <x-text-input wire:model="country" id="country" class="mt-2 block w-full" type="text" name="country" placeholder="Malaysia" />
                </div>
                <div>
                    <x-input-label for="state" :value="__('State')" />
                    <x-text-input wire:model="state" id="state" class="mt-2 block w-full" type="text" name="state" placeholder="Selangor" />
                </div>
                <div>
                    <x-input-label for="city" :value="__('City')" />
                    <x-text-input wire:model="city" id="city" class="mt-2 block w-full" type="text" name="city" placeholder="Petaling Jaya" />
                </div>
                <div>
                    <x-input-label for="postal_code" :value="__('Zip')" />
                    <x-text-input wire:model="postal_code" id="postal_code" class="mt-2 block w-full" type="text" name="postal_code" autocomplete="postal-code" placeholder="46000" />
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                </div>
            </div>
        </section>

        <section>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Badminton profile</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="preferred_hand" :value="__('Preferred hand')" />
                    <select wire:model="preferred_hand" id="preferred_hand" class="mt-2 block w-full rounded-md border-gray-300">
                        <option value="right">Right</option>
                        <option value="left">Left</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="primary_format" :value="__('Primary format')" />
                    <select wire:model="primary_format" id="primary_format" class="mt-2 block w-full rounded-md border-gray-300">
                        <option value="doubles">Doubles</option>
                        <option value="singles">Singles</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="club_name" :value="__('Club optional')" />
                    <x-text-input wire:model="club_name" id="club_name" class="mt-2 block w-full" type="text" name="club_name" placeholder="Club name" />
                </div>
            </div>
        </section>

        <section>
            <p class="text-xs font-black uppercase tracking-[.25em] text-[#d6a31d]">Password</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input wire:model="password" id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                    <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-4 border-t border-blue-950/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm font-bold text-blue-950/50 underline hover:text-[#071a80]" href="{{ route('login') }}" wire:navigate>
                {{ __('Already have an account?') }}
            </a>

            <button class="rounded-full bg-[#071a80] px-7 py-4 text-sm font-black uppercase tracking-[.12em] text-white hover:bg-[#0b2bc1]">
                {{ __('Create account') }}
            </button>
        </div>
    </form>
</div>
