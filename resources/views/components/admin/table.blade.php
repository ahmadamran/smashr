@props(['empty' => 'No records found.'])

<div class="overflow-hidden rounded-lg bg-white shadow-lg">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full text-left text-sm']) }}>
            {{ $slot }}
        </table>
    </div>
    @isset($emptyState)
        {{ $emptyState }}
    @endisset
</div>
