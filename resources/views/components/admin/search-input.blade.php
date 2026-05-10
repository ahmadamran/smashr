@props(['name' => 'search', 'placeholder' => 'Search'])

<label class="min-w-0 flex-1">
    <span class="sr-only">{{ $placeholder }}</span>
    <input name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}" class="w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950 placeholder:text-blue-950/40">
</label>
