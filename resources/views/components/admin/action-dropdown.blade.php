@props(['label' => 'Actions'])

<details class="relative inline-block text-left">
    <summary class="cursor-pointer list-none rounded-md border border-brand-ink/10 bg-white px-3 py-2 text-xs font-black uppercase text-brand-blue shadow-sm">
        {{ $label }}
    </summary>
    <div class="absolute right-0 z-20 mt-2 w-56 rounded-md border border-brand-ink/10 bg-white p-2 shadow-xl">
        <div class="grid gap-1 text-sm font-bold text-brand-ink">
            {{ $slot }}
        </div>
    </div>
</details>
