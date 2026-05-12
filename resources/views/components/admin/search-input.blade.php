@props(['name' => 'search', 'placeholder' => 'Search'])

<label class="min-w-0 flex-1">
    <span class="sr-only">{{ $placeholder }}</span>
    <input name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}" class="w-full rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink placeholder:text-brand-ink/40">
</label>
