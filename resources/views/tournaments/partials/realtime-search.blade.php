<div class="mb-6 rounded-lg bg-white p-5 shadow-lg">
    <label class="text-xs font-black uppercase tracking-[.2em] text-brand-green" for="{{ $id ?? 'tournament-search' }}">Search</label>
    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
        <input
            id="{{ $id ?? 'tournament-search' }}"
            type="search"
            wire:model.live.debounce.250ms="search"
            placeholder="Search players or school"
            class="w-full rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink placeholder:text-brand-ink/40"
        >
        @if ($search !== '')
            <button type="button" wire:click="$set('search', '')" class="rounded-md border border-brand-ink/10 px-4 py-2 text-sm font-black uppercase text-brand-blue">
                Clear
            </button>
        @endif
    </div>
</div>
