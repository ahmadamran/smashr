<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Matches</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.filter-bar>
            <select name="status" class="rounded-md border-blue-950/10 text-sm font-bold text-blue-950"><option value="">All statuses</option>@foreach(['pending_confirmation','confirmed','disputed','void'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select>
            <select name="tournament_id" class="rounded-md border-blue-950/10 text-sm font-bold text-blue-950"><option value="">All tournaments</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}" @selected(request('tournament_id') == $tournament->id)>{{ $tournament->name }}</option>@endforeach</select>
            <select name="event_id" class="rounded-md border-blue-950/10 text-sm font-bold text-blue-950"><option value="">All events</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>@endforeach</select>
            <x-admin.search-input name="court" placeholder="Court" />
        </x-admin.filter-bar>
        <x-admin.table>
            <thead class="bg-[#071a80] text-white"><tr>@foreach(['Match ID','Tournament','Event','Court','Players','Stage','Round','Status','Scheduled time','Winner','Actions'] as $heading)<th class="px-4 py-3 text-xs font-black uppercase">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-blue-950/10">
                @forelse ($matches as $match)
                    <tr class="align-top">
                        <td class="px-4 py-4 font-black text-[#071a80]">#{{ $match->id }}</td>
                        <td class="px-4 py-4">{{ $match->tournament?->name ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $match->tournamentCategory?->name ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $match->court_label ?: '-' }}</td>
                        <td class="px-4 py-4 text-sm">{{ $match->players->map(fn ($p) => ($p->user->playerProfile?->display_name ?? $p->user->name).' ('.$p->side.')')->join(' vs ') }}</td>
                        <td class="px-4 py-4">{{ $match->draw_group ?: '-' }}</td>
                        <td class="px-4 py-4">{{ $match->draw_round ?: '-' }}</td>
                        <td class="px-4 py-4"><x-admin.status-badge :status="$match->status" /></td>
                        <td class="px-4 py-4">{{ $match->scheduled_at?->format('M j, g:i A') ?: ($match->played_at?->format('M j, Y') ?: '-') }}</td>
                        <td class="px-4 py-4">Side {{ $match->winner_side }}</td>
                        <td class="px-4 py-4">
                            <x-admin.action-dropdown>
                                <a href="{{ route('admin.matches.show', $match) }}" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">View</a>
                                <a href="{{ route('admin.matches.edit', $match) }}" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">Edit / result / schedule</a>
                                <form method="POST" action="{{ route('admin.matches.confirm', $match) }}">@csrf @method('PATCH')<button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">Confirm</button></form>
                                <form method="POST" action="{{ route('admin.matches.dispute', $match) }}">@csrf @method('PATCH')<button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">Dispute</button></form>
                                <form method="POST" action="{{ route('admin.matches.void', $match) }}">@csrf @method('PATCH')<button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">Void</button></form>
                                <x-admin.confirm-dialog :action="route('admin.matches.destroy', $match)" label="Delete" message="Delete this match?" />
                            </x-admin.action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-10 text-center font-bold text-blue-950/50">No matches found.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$matches" />
    </div>
</x-app-layout>
