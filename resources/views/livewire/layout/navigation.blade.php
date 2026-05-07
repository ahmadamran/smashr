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

<nav x-data="{ open: false }" class="border-b border-blue-950/10 bg-white text-[#071a80]">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" wire:navigate class="flex items-center">
                        <img src="{{ asset('images/smashr-wordmark.png') }}" alt="SmashR" width="116" height="29" class="h-7 w-[116px] object-contain" style="width: 116px; height: 29px;">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden items-center gap-8 text-sm font-extrabold uppercase md:ms-10 md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Dashboard') }}</a>
                    @endauth
                    <a href="{{ route('rankings') }}" wire:navigate class="{{ request()->routeIs('rankings') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Rankings') }}</a>
                    <a href="{{ route('matches.index') }}" wire:navigate class="{{ request()->routeIs('matches.index') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Matches') }}</a>
                    <a href="{{ route('clubs.index') }}" wire:navigate class="{{ request()->routeIs('clubs.*') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Clubs') }}</a>
                    <a href="{{ route('tournaments.index') }}" wire:navigate class="{{ request()->routeIs('tournaments.*') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Tournaments') }}</a>
                    @auth
                        <a href="{{ route('matches.create') }}" wire:navigate class="{{ request()->routeIs('matches.create') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Submit Match') }}</a>
                        <a href="{{ route('organizer.tournaments.index') }}" wire:navigate class="{{ request()->routeIs('organizer.*') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Organizer') }}</a>
                        @if (auth()->user()->hasRole('superadmin'))
                            <a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ request()->routeIs('admin.*') ? 'text-[#d6a31d]' : 'hover:text-[#d6a31d]' }}">{{ __('Admin') }}</a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-bold uppercase leading-4 text-[#071a80] transition hover:text-[#d6a31d] focus:outline-none">
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

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
                @else
                    <div class="flex items-center gap-4 text-sm font-bold uppercase">
                        <a href="{{ route('login') }}" wire:navigate class="text-[#071a80] hover:text-[#d6a31d]">Login</a>
                        <a href="{{ route('register') }}" wire:navigate class="rounded-full bg-[#071a80] px-4 py-2 text-white">Register</a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = true" class="inline-flex items-center justify-center p-2 text-[#071a80] focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-white sm:hidden" style="display: none;">
        <div class="flex h-20 items-center justify-between border-b border-blue-950/10 px-6">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" wire:navigate @click="open = false">
                <img src="{{ asset('images/smashr-wordmark.png') }}" alt="SmashR" width="136" height="34" class="h-[34px] w-[136px] object-contain" style="width: 136px; height: 34px;">
            </a>
            <button @click="open = false" class="text-blue-950/45">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M6 6l12 12M18 6 6 18" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-10">
            @php
                $mobileLinks = [
                    ['label' => 'Rankings', 'url' => route('rankings'), 'active' => request()->routeIs('rankings')],
                    ['label' => 'Matches', 'url' => route('matches.index'), 'active' => request()->routeIs('matches.index')],
                    ['label' => 'Clubs', 'url' => route('clubs.index'), 'active' => request()->routeIs('clubs.*')],
                    ['label' => 'Tournaments', 'url' => route('tournaments.index'), 'active' => request()->routeIs('tournaments.*')],
                ];
                if (auth()->check()) {
                    array_unshift($mobileLinks, ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard')]);
                    $mobileLinks[] = ['label' => 'Submit Match', 'url' => route('matches.create'), 'active' => request()->routeIs('matches.create')];
                    $mobileLinks[] = ['label' => 'Organizer', 'url' => route('organizer.tournaments.index'), 'active' => request()->routeIs('organizer.*')];
                    if (auth()->user()->hasRole('superadmin')) {
                        $mobileLinks[] = ['label' => 'Admin', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.*')];
                    }
                }
            @endphp

            <div class="divide-y divide-dashed divide-blue-950/15 border-y border-dashed border-blue-950/15">
                @foreach ($mobileLinks as $link)
                    <a href="{{ $link['url'] }}" wire:navigate @click="open = false" class="flex items-center justify-between py-6 text-2xl font-black uppercase tracking-[.18em] {{ $link['active'] ? 'text-[#071a80]' : 'text-[#1d3448]' }}">
                        <span>{{ $link['label'] }}</span>
                        @if ($link['active'])
                            <span class="h-2 w-2 rounded-full bg-[#d6a31d]"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            @auth
                <div class="mt-8 flex items-center justify-between gap-4">
                    <a href="{{ route('profile') }}" wire:navigate @click="open = false" class="text-sm font-black uppercase tracking-[.18em] text-[#071a80]">Profile</a>
                    <button wire:click="logout" class="text-sm font-black uppercase tracking-[.18em] text-blue-950/50">Log out</button>
                </div>
            @else
                <div class="mt-8 grid grid-cols-2 gap-3">
                    <a href="{{ route('login') }}" wire:navigate @click="open = false" class="rounded-full border border-blue-950/15 px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-[#071a80]">Login</a>
                    <a href="{{ route('register') }}" wire:navigate @click="open = false" class="rounded-full bg-[#071a80] px-5 py-3 text-center text-sm font-black uppercase tracking-[.12em] text-white">Register</a>
                </div>
            @endauth
        </div>
    </div>
</nav>
