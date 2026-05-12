<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">{{ $tournament->name }}</p>
            <h1 class="text-3xl font-black text-brand-blue">{{ $category->name }} draw</h1>
        </div>
    </x-slot>

    <livewire:tournaments.draw :tournament="$tournament" :category="$category" />
</x-app-layout>
