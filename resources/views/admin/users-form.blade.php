<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">{{ $user ? 'Edit user' : 'Create user' }}</h1></x-slot>
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout :title="$user ? 'Edit user' : 'Create user'" subtitle="Manage account, profile, role and club assignment from a dedicated form.">
            <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                @if ($user) @method('PATCH') @endif
                <label class="font-bold text-[#071a80]">Name
                    <input name="name" value="{{ old('name', $user?->name) }}" class="mt-1 w-full rounded-md border-blue-950/10">
                </label>
                <label class="font-bold text-[#071a80]">Email
                    <input name="email" type="email" value="{{ old('email', $user?->email) }}" class="mt-1 w-full rounded-md border-blue-950/10">
                </label>
                <label class="font-bold text-[#071a80]">{{ $user ? 'New password' : 'Password' }}
                    <input name="password" type="password" class="mt-1 w-full rounded-md border-blue-950/10">
                </label>
                <x-admin.phone-input
                    :value="$user?->playerProfile?->phone_number"
                    :country="$user?->playerProfile?->country ?: 'Malaysia'"
                />
                <label class="font-bold text-[#071a80]">Club
                    <select name="club_id" class="mt-1 w-full rounded-md border-blue-950/10">
                        <option value="">Independent</option>
                        @foreach ($clubs as $club)
                            <option value="{{ $club->id }}" @selected(old('club_id', $user?->clubs->first()?->id) === $club->id)>{{ $club->name }}</option>
                        @endforeach
                    </select>
                </label>
                @unless ($user)
                    <label class="font-bold text-[#071a80]">Initial SMASHR points
                        <input name="smashr_points" type="number" value="{{ old('smashr_points', 0) }}" class="mt-1 w-full rounded-md border-blue-950/10">
                    </label>
                @endunless
                <div class="flex gap-3 md:col-span-2">
                    <button class="rounded-md bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white">{{ $user ? 'Save user' : 'Create user' }}</button>
                    <a href="{{ route('admin.users') }}" class="rounded-md border border-blue-950/10 px-5 py-3 text-xs font-black uppercase text-[#071a80]">Cancel</a>
                </div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
