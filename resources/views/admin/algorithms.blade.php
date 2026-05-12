<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Rating algorithms</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        @if ($preview)
            <div class="mb-6 rounded-lg bg-brand-mist p-5 text-brand-blue"><h2 class="font-black">Recalculation preview</h2><p>{{ $preview['message'] }}</p></div>
        @endif
        <div class="mb-6 flex justify-end"><a href="{{ route('admin.algorithms.create') }}" class="rounded-md bg-brand-blue px-4 py-3 text-xs font-black uppercase text-white">Create algorithm</a></div>
        <x-admin.table>
            <thead class="bg-brand-blue text-white"><tr>@foreach(['Name','Version','Status','Starting rating','Min rating','Max rating','Base delta','Margin weight','Max margin bonus','Rating scale divisor','Activated date','Actions'] as $heading)<th class="px-4 py-3 text-xs font-black uppercase">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-brand-ink/10">
                @forelse ($algorithms as $algorithm)
                    <tr>
                        <td class="px-4 py-4 font-black text-brand-blue">{{ $algorithm->name }}</td>
                        <td class="px-4 py-4">{{ $algorithm->version }}</td>
                        <td class="px-4 py-4"><x-admin.status-badge :status="$algorithm->status" /></td>
                        @foreach (['starting_rating','min_rating','max_rating','base_delta','margin_weight','max_margin_bonus','rating_scale_divisor'] as $key)
                            <td class="px-4 py-4">{{ $algorithm->settings[$key] ?? '-' }}</td>
                        @endforeach
                        <td class="px-4 py-4">{{ $algorithm->activated_at?->format('M j, Y') ?: '-' }}</td>
                        <td class="px-4 py-4">
                            <x-admin.action-dropdown>
                                @if ($algorithm->status !== 'active')
                                    <a href="{{ route('admin.algorithms.edit', $algorithm) }}" class="rounded px-3 py-2 hover:bg-brand-surface">Edit</a>
                                    <form method="POST" action="{{ route('admin.algorithms.activate', $algorithm) }}">@csrf @method('PATCH')<button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Activate</button></form>
                                    <form method="POST" action="{{ route('admin.algorithms.archive', $algorithm) }}">@csrf @method('PATCH')<button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Archive</button></form>
                                @endif
                                <form method="POST" action="{{ route('admin.algorithms.duplicate', $algorithm) }}">@csrf<button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Duplicate</button></form>
                                <form method="POST" action="{{ route('admin.algorithms.recalculate.preview', $algorithm) }}">@csrf<button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Preview recalculation</button></form>
                                <form method="POST" action="{{ route('admin.algorithms.recalculate.apply', $algorithm) }}" onsubmit="return confirm('Apply recalculation to ratings?')">@csrf<button class="w-full rounded px-3 py-2 text-left hover:bg-red-50">Apply recalculation</button></form>
                                @if ($algorithm->status === 'draft')
                                    <x-admin.confirm-dialog :action="route('admin.algorithms.destroy', $algorithm)" label="Delete draft" message="Delete this draft algorithm?" />
                                @endif
                            </x-admin.action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-4 py-10 text-center font-bold text-brand-ink/50">No algorithms found.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$algorithms" />
    </div>
</x-app-layout>
