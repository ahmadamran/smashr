<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $algorithm ? 'Edit algorithm' : 'Create algorithm' }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <x-admin.form-layout :title="$algorithm ? 'Edit algorithm draft' : 'Create algorithm draft'">
            <form method="POST" action="{{ $algorithm ? route('admin.algorithms.update', $algorithm) : route('admin.algorithms.store') }}" class="grid gap-4 md:grid-cols-4">
                @csrf
                @if ($algorithm) @method('PATCH') @endif
                <label class="font-bold text-brand-blue md:col-span-2">Name<input name="name" value="{{ old('name', $algorithm?->name) }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                <label class="font-bold text-brand-blue md:col-span-2">Version<input name="version" value="{{ old('version', $algorithm?->version) }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                @foreach (\Modules\Ratings\Models\RatingAlgorithm::DEFAULT_SETTINGS as $key => $value)
                    <label class="font-bold text-brand-blue">{{ str_replace('_', ' ', $key) }}<input name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $algorithm?->settings[$key] ?? $value) }}" class="mt-1 w-full rounded-md border-brand-ink/10"></label>
                @endforeach
                <div class="flex gap-3 md:col-span-4"><button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Save algorithm</button><a href="{{ route('admin.algorithms') }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a></div>
            </form>
        </x-admin.form-layout>
    </div>
</x-app-layout>
