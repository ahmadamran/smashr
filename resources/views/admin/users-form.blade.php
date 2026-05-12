<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $user ? 'Edit user' : 'Create user' }}</h1></x-slot>
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout :title="$user ? 'Edit user' : 'Create user'" subtitle="Manage account, profile, role and club assignment from a dedicated form.">
            <form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                @if ($user) @method('PATCH') @endif
                <label class="font-bold text-brand-blue">Name
                    <input name="name" value="{{ old('name', $user?->name) }}" class="mt-1 w-full rounded-md border-brand-ink/10">
                </label>
                <label class="font-bold text-brand-blue">Email
                    <input name="email" type="email" value="{{ old('email', $user?->email) }}" class="mt-1 w-full rounded-md border-brand-ink/10">
                </label>
                <label class="font-bold text-brand-blue">{{ $user ? 'New password' : 'Password' }}
                    <input name="password" type="password" class="mt-1 w-full rounded-md border-brand-ink/10">
                </label>
                <x-admin.phone-input
                    :value="$user?->playerProfile?->phone_number"
                    :country="$user?->playerProfile?->country ?: 'Malaysia'"
                />
                <label class="font-bold text-brand-blue">Club
                    <select name="club_id" class="mt-1 w-full rounded-md border-brand-ink/10">
                        <option value="">Independent</option>
                        @foreach ($clubs as $club)
                            <option value="{{ $club->id }}" @selected(old('club_id', $user?->clubs->first()?->id) === $club->id)>{{ $club->name }}</option>
                        @endforeach
                    </select>
                </label>
                @unless ($user)
                    <label class="font-bold text-brand-blue">Initial SMASHR points
                        <input name="smashr_points" type="number" value="{{ old('smashr_points', 0) }}" class="mt-1 w-full rounded-md border-brand-ink/10">
                    </label>
                @endunless
                <div class="flex gap-3 md:col-span-2">
                    <button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">{{ $user ? 'Save user' : 'Create user' }}</button>
                    <a href="{{ route('admin.users') }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a>
                </div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
