<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-brand-ink/10 bg-white text-brand-blue">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-5">
        <div class="flex h-16 items-center justify-between">
            <!-- Logo -->
            <a href="{{ url('/') }}" wire:navigate class="flex items-center">
                <x-application-logo />
            </a>

            <!-- Navigation Links -->
            <div class="hidden items-center gap-8 text-sm font-extrabold uppercase md:flex">
                <a href="{{ route('rankings') }}" wire:navigate class="{{ request()->routeIs('rankings') ? 'text-brand-green' : 'hover:text-brand-green' }}">{{ __('Rankings') }}</a>
                <a href="{{ route('tournaments.index') }}" wire:navigate class="{{ request()->routeIs('tournaments.*') ? 'text-brand-green' : 'hover:text-brand-green' }}">{{ __('Tournaments') }}</a>
                <a href="{{ route('clubs.index') }}" wire:navigate class="{{ request()->routeIs('clubs.*') ? 'text-brand-green' : 'hover:text-brand-green' }}">{{ __('Clubs') }}</a>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden items-center gap-5 text-sm font-bold uppercase md:flex">
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-bold uppercase leading-4 text-brand-blue transition hover:text-brand-green focus:outline-none">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('profile.player')" wire:navigate>
                            {{ __('Player Settings') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-brand-blue hover:text-brand-green">Login</a>
                    <a href="{{ route('register') }}" wire:navigate class="rounded-full bg-brand-blue px-4 py-2 text-white hover:bg-brand-blue-dark">Register</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = true" class="inline-flex items-center justify-center p-2 text-brand-blue focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    @auth
        <div class="hidden border-t border-brand-ink/10 bg-brand-surface md:block">
            <div class="mx-auto flex max-w-7xl items-center gap-6 px-5 py-3 text-xs font-black uppercase tracking-[.12em] text-brand-ink/60">
                <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'text-brand-green' : 'hover:text-brand-blue' }}">{{ __('Dashboard') }}</a>
                <a href="{{ route('organizer.tournaments.index') }}" wire:navigate class="{{ request()->routeIs('organizer.tournaments.*') ? 'text-brand-green' : 'hover:text-brand-blue' }}">{{ __('My Tournaments') }}</a>
                @if (auth()->user()->hasRole('superadmin'))
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ request()->routeIs('admin.*') ? 'text-brand-green' : 'hover:text-brand-blue' }}">{{ __('Admin') }}</a>
                @endif
            </div>
        </div>
    @endauth

    <!-- Responsive Navigation Menu -->
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto bg-white sm:hidden" style="display: none;">
        <div class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-brand-ink/10 bg-white px-6">
            <a href="{{ url('/') }}" wire:navigate @click="open = false">
                <x-application-logo />
            </a>
            <button @click="open = false" class="text-brand-ink/45">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M6 6l12 12M18 6 6 18" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-6 pb-32">
            @php
                $mainLinks = [
                    ['label' => 'Rankings', 'url' => route('rankings'), 'active' => request()->routeIs('rankings')],
                    ['label' => 'Tournaments', 'url' => route('tournaments.index'), 'active' => request()->routeIs('tournaments.*')],
                    ['label' => 'Clubs', 'url' => route('clubs.index'), 'active' => request()->routeIs('clubs.*')],
                ];
                $playerLinks = [];
                $organizerLinks = [];
                $accountLinks = [];

                if (auth()->check()) {
                    $playerLinks = [
                        ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                    ];
                    $organizerLinks = [
                        ['label' => 'My Tournaments', 'url' => route('organizer.tournaments.index'), 'active' => request()->routeIs('organizer.tournaments.*')],
                    ];
                    if (auth()->user()->hasRole('superadmin')) {
                        $organizerLinks[] = ['label' => 'Admin', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.*')];
                    }
                    $accountLinks = [
                        ['label' => 'Profile', 'url' => route('profile'), 'active' => request()->routeIs('profile')],
                        ['label' => 'Player Settings', 'url' => route('profile.player'), 'active' => request()->routeIs('profile.player')],
                    ];
                }
            @endphp

            @foreach ([
                'Main' => $mainLinks,
                'Organizer' => $organizerLinks,
                'Player' => $playerLinks,
                'Account' => $accountLinks,
            ] as $groupLabel => $links)
                @if (count($links))
                    <div class="{{ $loop->first ? '' : 'mt-5' }}">
                        <p class="mb-2 text-[11px] font-black uppercase tracking-[.22em] text-brand-ink/35">{{ $groupLabel }}</p>
                        <div class="divide-y divide-dashed divide-brand-ink/15 border-y border-dashed border-brand-ink/15">
                            @foreach ($links as $link)
                                <a href="{{ $link['url'] }}" wire:navigate @click="open = false" class="flex items-center justify-between py-3.5 text-lg font-black uppercase tracking-[.14em] {{ $link['active'] ? 'text-brand-blue' : 'text-brand-ink' }}">
                                    <span>{{ $link['label'] }}</span>
                                    @if ($link['active'])
                                        <span class="h-2 w-2 rounded-full bg-brand-green"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @auth
                <div class="mt-8 flex items-center justify-between gap-4">
                    <span class="text-sm font-black uppercase tracking-[.18em] text-brand-blue">{{ auth()->user()->name }}</span>
                    <button wire:click="logout" class="text-sm font-black uppercase tracking-[.18em] text-brand-ink/50">Log out</button>
                </div>
            @else
                <div class="mt-8 grid grid-cols-2 gap-3">
                    <a href="{{ route('login') }}" wire:navigate @click="open = false" class="rounded-full border border-brand-ink/15 px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-brand-blue">Login</a>
                    <a href="{{ route('register') }}" wire:navigate @click="open = false" class="rounded-full bg-brand-blue px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-white">Register</a>
                </div>
            @endauth
        </div>
    </div>
</nav>
