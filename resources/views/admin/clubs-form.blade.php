<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $club ? 'Edit club' : 'Create club' }}</h1></x-slot>
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout :title="$club ? 'Edit club' : 'Create club'">
            <form method="POST" action="{{ $club ? route('admin.clubs.update', $club) : route('admin.clubs.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                @if ($club) @method('PATCH') @endif
                @foreach (['name' => 'Club name', 'country' => 'Country', 'state' => 'State', 'city' => 'City'] as $field => $label)
                    <label class="font-bold text-brand-blue">{{ $label }}<input name="{{ $field }}" value="{{ old($field, $club?->{$field}) }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                @endforeach
                <label class="font-bold text-brand-blue md:col-span-2">Description<textarea name="description" class="mt-1 w-full rounded-md border-brand-ink/10">{{ old('description', $club?->description) }}</textarea></label>
                <div class="flex gap-3 md:col-span-2">
                    <button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Save club</button>
                    <a href="{{ route('admin.clubs') }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a>
                </div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
