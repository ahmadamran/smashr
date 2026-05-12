<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Clubs</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-admin.filter-bar><x-admin.search-input placeholder="Search clubs" /></x-admin.filter-bar>
            <a href="{{ route('admin.clubs.create') }}" class="rounded-md bg-brand-blue px-4 py-3 text-xs font-black uppercase text-white">Create club</a>
        </div>
        <x-admin.table>
            <thead class="bg-brand-blue text-white"><tr>@foreach(['ID','Club name','Country','State','City','Member count','Tournament count','Created date','Actions'] as $heading)<th class="px-4 py-3 text-xs font-black uppercase">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-brand-ink/10">
                @forelse ($clubs as $club)
                    <tr>
                        <td class="px-4 py-4 font-mono text-xs text-brand-ink/50">{{ $club->id }}</td>
                        <td class="px-4 py-4 font-black text-brand-blue">{{ $club->name }}</td>
                        <td class="px-4 py-4">{{ $club->country ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $club->state ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $club->city ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $club->members_count }}</td>
                        <td class="px-4 py-4">{{ $club->tournaments_count }}</td>
                        <td class="px-4 py-4">{{ $club->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-4">
                            <x-admin.action-dropdown>
                                <a href="{{ route('admin.clubs.show', $club) }}" class="rounded px-3 py-2 hover:bg-brand-surface">View</a>
                                <a href="{{ route('admin.clubs.edit', $club) }}" class="rounded px-3 py-2 hover:bg-brand-surface">Edit</a>
                                <form method="POST" action="{{ route('admin.clubs.merge', $club) }}" class="border-y border-brand-ink/10 py-2" onsubmit="return confirm('Merge these duplicate clubs into {{ addslashes($club->name) }}? This moves members, tournaments, and matches, then deletes the duplicate club records.');">
                                    @csrf
                                    <label class="mb-1 block px-3 text-[10px] font-black uppercase tracking-[0.18em] text-brand-green">Merge into this</label>
                                    <input name="source_ids" placeholder="Duplicate IDs" class="mb-2 w-full rounded-md border-brand-ink/10 px-3 py-2 text-xs font-bold text-brand-ink" required>
                                    <button class="w-full rounded bg-brand-blue px-3 py-2 text-left text-xs font-black uppercase text-white hover:bg-brand-blue-dark">Merge clubs</button>
                                </form>
                                <x-admin.confirm-dialog :action="route('admin.clubs.destroy', $club)" label="Delete" message="Delete this club?" />
                            </x-admin.action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center font-bold text-brand-ink/50">No clubs found.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$clubs" />
    </div>
</x-app-layout>
