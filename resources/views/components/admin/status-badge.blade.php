@props(['status'])

@php
    $value = strtolower((string) $status);
    $classes = match ($value) {
        'active', 'published', 'confirmed', 'approved', 'open' => 'bg-green-50 text-green-700',
        'suspended', 'void', 'archived', 'closed' => 'bg-red-50 text-red-700',
        'disputed', 'pending_confirmation', 'pending', 'draft' => 'bg-amber-50 text-amber-800',
        default => 'bg-brand-mist text-brand-blue',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-3 py-1 text-xs font-black uppercase {$classes}"]) }}>
    {{ str_replace('_', ' ', $status ?: 'unknown') }}
</span>
