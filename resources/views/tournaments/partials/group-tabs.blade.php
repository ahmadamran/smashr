@php
    $groups = $category->entrants
        ->where('status', 'approved')
        ->pluck('group_name')
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

@if ($groups->isNotEmpty())
    <div class="mb-6 overflow-x-auto">
        <div class="flex min-w-max gap-2">
            <a href="{{ route('tournaments.draw', [$tournament, $category]) }}" class="rounded-md px-4 py-2 text-xs font-black uppercase {{ empty($groupName ?? null) ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">All groups</a>
            @foreach ($groups as $name)
                @php($slug = str($name)->slug())
                <a href="{{ route('tournaments.draw.group', [$tournament, $category, $slug]) }}" class="rounded-md px-4 py-2 text-xs font-black uppercase {{ ($groupName ?? null) === $name ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">{{ $name }}</a>
            @endforeach
        </div>
    </div>
@endif
