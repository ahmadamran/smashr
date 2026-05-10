<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Users</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-admin.filter-bar>
                <x-admin.search-input placeholder="Search name or email" />
                <select name="status" class="rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </select>
                <select name="role" class="rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    <option value="">All roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                </select>
                <select name="sort" class="rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    <option value="">Newest</option>
                    <option value="name" @selected(request('sort') === 'name')>Name</option>
                </select>
            </x-admin.filter-bar>
            <a href="{{ route('admin.users.create') }}" class="rounded-md bg-[#071a80] px-4 py-3 text-xs font-black uppercase text-white">Create user</a>
        </div>

        <x-admin.table>
            <thead class="bg-[#071a80] text-white">
                <tr>
                    @foreach (['Name', 'Email', 'Club', 'Role', 'Status', 'SMASHR points', 'Rating', 'Matches played', 'Last active', 'Actions'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-black uppercase">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-blue-950/10">
                @forelse ($users as $user)
                    <tr class="align-top">
                        <td class="px-4 py-4 font-black text-[#071a80]">{{ $user->name }}</td>
                        <td class="px-4 py-4 text-blue-950/70">{{ $user->email }}</td>
                        <td class="px-4 py-4">{{ $user->clubs->pluck('name')->join(', ') ?: 'Independent' }}</td>
                        <td class="px-4 py-4">{{ $user->roles->pluck('name')->join(', ') ?: 'Player' }}</td>
                        <td class="px-4 py-4"><x-admin.status-badge :status="$user->suspended_at ? 'suspended' : 'active'" /></td>
                        <td class="px-4 py-4 font-black">{{ $user->playerProfile?->smashr_points ?? 0 }}</td>
                        <td class="px-4 py-4">{{ $user->playerProfile?->singles_rating ?? '3.500' }} / {{ $user->playerProfile?->doubles_rating ?? '3.500' }}</td>
                        <td class="px-4 py-4">{{ $user->match_players_count }}</td>
                        <td class="px-4 py-4">{{ $user->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-4">
                            <x-admin.action-dropdown>
                                <a href="{{ route('admin.users.show', $user) }}" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">View</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">Edit</a>
                                <form method="POST" action="{{ route('admin.users.suspension', $user) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="suspended" value="{{ $user->suspended_at ? 0 : 1 }}">
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">{{ $user->suspended_at ? 'Activate' : 'Suspend' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.superadmin', $user) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="enabled" value="{{ $user->hasRole('superadmin') ? 0 : 1 }}">
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">{{ $user->hasRole('superadmin') ? 'Remove admin' : 'Make admin' }}</button>
                                </form>
                                <a href="{{ route('admin.users.show', $user) }}#points" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">Manage SMASHR points</a>
                                <form method="POST" action="{{ route('admin.users.ratings.regenerate', $user) }}">@csrf
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">Regenerate rating</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.points.regenerate', $user) }}">@csrf
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-[#f3f6fb]">Regenerate SMASHR points</button>
                                </form>
                                <a href="{{ route('admin.matches') }}?user={{ $user->id }}" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">Add match</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded px-3 py-2 hover:bg-[#f3f6fb]">Link / unlink club</a>
                                <x-admin.confirm-dialog :action="route('admin.users.destroy', $user)" label="Delete" message="Delete this user?" />
                            </x-admin.action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-10 text-center font-bold text-blue-950/50">No users found.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$users" />
    </div>
</x-app-layout>
