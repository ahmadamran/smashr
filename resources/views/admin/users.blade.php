<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Manage users</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="overflow-hidden rounded-lg bg-white shadow-lg">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#071a80] text-white"><tr><th class="p-4">User</th><th class="p-4">Profile</th><th class="p-4">Roles</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead>
                <tbody class="divide-y">
                    @foreach ($users as $user)
                        <tr>
                            <td class="p-4"><strong>{{ $user->name }}</strong><p>{{ $user->email }}</p></td>
                            <td class="p-4">{{ $user->playerProfile?->display_name ?? 'No profile' }}<p>{{ $user->clubs->pluck('name')->join(', ') ?: 'Independent' }}</p></td>
                            <td class="p-4">{{ $user->roles->pluck('name')->join(', ') ?: 'user' }}</td>
                            <td class="p-4">{{ $user->suspended_at ? 'Suspended' : 'Active' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.users.superadmin', $user) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="enabled" value="{{ $user->hasRole('superadmin') ? 0 : 1 }}">
                                        <button class="rounded bg-[#071a80] px-3 py-2 text-xs font-bold text-white">{{ $user->hasRole('superadmin') ? 'Remove admin' : 'Make admin' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.suspension', $user) }}">@csrf @method('PATCH')
                                        <input type="hidden" name="suspended" value="{{ $user->suspended_at ? 0 : 1 }}">
                                        <button class="rounded border px-3 py-2 text-xs font-bold">{{ $user->suspended_at ? 'Reactivate' : 'Suspend' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links('pagination.smashr') }}</div>
    </div>
</x-app-layout>
