<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Users</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <x-admin.filter-bar>
                <x-admin.search-input placeholder="Search name or email" />
                <select name="status" class="rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </select>
                <select name="role" class="rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                    <option value="">All roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                </select>
                <select name="sort" class="rounded-md border-brand-ink/10 text-sm font-bold text-brand-ink">
                    <option value="">Newest</option>
                    <option value="name" @selected(request('sort') === 'name')>Name</option>
                </select>
            </x-admin.filter-bar>
            <a href="{{ route('admin.users.create') }}" class="rounded-md bg-brand-blue px-4 py-3 text-xs font-black uppercase text-white">Create user</a>
        </div>

        <x-admin.table>
            <thead class="bg-brand-blue text-white">
                <tr>
                    @foreach (['ID', 'Name', 'Email', 'Club', 'Role', 'Status', 'SMASHR points', 'Rating', 'Matches played', 'Last active', 'Actions'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3 text-xs font-black uppercase">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-ink/10">
                @forelse ($users as $user)
                    <tr class="align-top">
                        <td class="px-4 py-4 font-mono text-xs text-brand-ink/50">{{ $user->id }}</td>
                        <td class="px-4 py-4 font-black text-brand-blue">{{ $user->name }}</td>
                        <td class="px-4 py-4 text-brand-ink/70">{{ $user->email }}</td>
                        <td class="px-4 py-4">{{ $user->clubs->pluck('name')->join(', ') ?: 'Independent' }}</td>
                        <td class="px-4 py-4">{{ $user->roles->pluck('name')->join(', ') ?: 'Player' }}</td>
                        <td class="px-4 py-4"><x-admin.status-badge :status="$user->suspended_at ? 'suspended' : 'active'" /></td>
                        <td class="px-4 py-4 font-black">{{ $user->playerProfile?->smashr_points ?? 0 }}</td>
                        <td class="px-4 py-4">{{ $user->playerProfile?->singles_rating ?? '3.500' }} / {{ $user->playerProfile?->doubles_rating ?? '3.500' }} / {{ $user->playerProfile?->mixed_rating ?? '3.500' }}</td>
                        <td class="px-4 py-4">{{ $user->match_players_count }}</td>
                        <td class="px-4 py-4">{{ $user->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-4">
                            <x-admin.action-dropdown>
                                <a href="{{ route('admin.users.show', $user) }}" class="rounded px-3 py-2 hover:bg-brand-surface">View</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded px-3 py-2 hover:bg-brand-surface">Edit</a>
                                <form method="POST" action="{{ route('admin.users.suspension', $user) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="suspended" value="{{ $user->suspended_at ? 0 : 1 }}">
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">{{ $user->suspended_at ? 'Activate' : 'Suspend' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.superadmin', $user) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="enabled" value="{{ $user->hasRole('superadmin') ? 0 : 1 }}">
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">{{ $user->hasRole('superadmin') ? 'Remove admin' : 'Make admin' }}</button>
                                </form>
                                <a href="{{ route('admin.users.show', $user) }}#points" class="rounded px-3 py-2 hover:bg-brand-surface">Manage SMASHR points</a>
                                <form method="POST" action="{{ route('admin.users.ratings.regenerate', $user) }}">@csrf
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Regenerate rating</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.points.regenerate', $user) }}">@csrf
                                    <button class="w-full rounded px-3 py-2 text-left hover:bg-brand-surface">Regenerate SMASHR points</button>
                                </form>
                                <a href="{{ route('admin.matches.create', ['user' => $user->id]) }}" class="rounded px-3 py-2 hover:bg-brand-surface">Add match</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded px-3 py-2 hover:bg-brand-surface">Link / unlink club</a>
                                <form method="POST" action="{{ route('admin.users.merge', $user) }}" class="border-y border-brand-ink/10 py-2" onsubmit="return confirm('Merge these duplicate users into {{ addslashes($user->name) }}? This moves clubs, matches, tournament entries, points history, and roles, then deletes the duplicate user records.');">
                                    @csrf
                                    <label class="mb-1 block px-3 text-[10px] font-black uppercase tracking-[0.18em] text-brand-green">Merge into this</label>
                                    <input name="source_ids" placeholder="Duplicate IDs" class="mb-2 w-full rounded-md border-brand-ink/10 px-3 py-2 text-xs font-bold text-brand-ink" required>
                                    <button class="w-full rounded bg-brand-blue px-3 py-2 text-left text-xs font-black uppercase text-white hover:bg-brand-blue-dark">Merge users</button>
                                </form>
                                <x-admin.confirm-dialog :action="route('admin.users.destroy', $user)" label="Delete" message="Delete this user?" />
                            </x-admin.action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-10 text-center font-bold text-brand-ink/50">No users found.</td></tr>
                @endforelse
            </tbody>
        </x-admin.table>
        <x-admin.pagination :paginator="$users" />
    </div>
</x-app-layout>
