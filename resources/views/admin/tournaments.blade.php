<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Tournaments</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-admin.filter-bar>
                <x-admin.search-input placeholder="Search tournaments" />
                <select name="status" class="rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink"><option value="">All statuses</option>@foreach(['draft','published','archived'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            </x-admin.filter-bar>
            <a href="{{ route('admin.tournaments.create') }}" class="rounded-md bg-brand-blue px-4 py-3 text-xs font-black uppercase text-white">Create tournament</a>
        </div>
        <x-admin.table>
            <thead class="bg-brand-blue text-white"><tr>@foreach(['Tournament name','Club','Country','State','City','Start date','End date','Match count','Status','Actions'] as $heading)<th class="px-4 py-3 text-xs font-black uppercase">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-brand-ink/10">
                @forelse ($tournaments as $tournament)
                    <tr>
                        <td class="px-4 py-4 font-black text-brand-blue">{{ $tournament->name }}</td>
                        <td class="px-4 py-4">{{ $tournament->club?->name ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $tournament->country ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $tournament->state ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $tournament->city ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $tournament->starts_at?->format('M j, Y') ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $tournament->ends_at?->format('M j, Y') ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $tournament->matches_count }}</td>
                        <td class="px-4 py-4"><x-admin.status-badge :status="$tournament->status" /></td>
                        <td class="px-4 py-4">
                            <x-admin.action-dropdown>
                                <a href="{{ route('admin.tournaments.show', $tournament) }}" class="rounded px-3 py-2 hover:bg-brand-surface">View</a>
                                <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="rounded px-3 py-2 hover:bg-brand-surface">Edit</a>
                                <form method="POST" action="{{ route('admin.tournaments.archive', $tournament) }}">@csrf @method('PATCH')<button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Archive</button></form>
                                <x-admin.confirm-dialog :action="route('admin.tournaments.destroy', $tournament)" label="Delete" message="Delete this tournament?" />
                            </x-admin.action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-10 text-center font-bold text-brand-ink/50">No tournaments found.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$tournaments" />
    </div>
</x-app-layout>
