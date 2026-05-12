@props(['title', 'subtitle' => null])

<section class="rounded-lg bg-white p-6 shadow-lg">
    <div class="mb-5">
        <h2 class="text-2xl font-black text-brand-blue">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm font-bold text-brand-ink/60">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
