<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Tournament setup</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">Create category</h2>
                <p class="mt-2 text-sm font-bold text-brand-ink/60">Add one event category, such as Boys Under 12, Amateur Doubles, or Open Mixed.</p>
            </div>

            <form method="POST" action="{{ route('organizer.tournaments.categories.store', $tournament) }}" class="mt-6 grid gap-4 md:grid-cols-2">@csrf
                <label class="font-bold text-brand-blue md:col-span-2">Category name
                    <input name="name" value="{{ old('name') }}" placeholder="Category name" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="font-bold text-brand-blue">Format
                    <select name="format" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach (['singles' => 'Singles', 'doubles' => 'Doubles', 'mixed' => 'Mixed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('format', 'singles') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Level label
                    <input name="level_label" value="{{ old('level_label') }}" placeholder="Under 12 Boys" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="font-bold text-brand-blue">Draw mode
                    <select name="draw_mode" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach (['single_elimination' => 'Single elimination', 'round_robin' => 'Round robin'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('draw_mode', 'single_elimination') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Group size
                    <select name="group_size" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach ([4, 3, 5, 6] as $size)
                            <option value="{{ $size }}" @selected((int) old('group_size', 4) === $size)>Group of {{ $size }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Max entrants
                    <input name="max_entrants" type="number" min="2" value="{{ old('max_entrants') }}" placeholder="No limit" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="font-bold text-brand-blue">Status
                    <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach (['published' => 'Published', 'draft' => 'Draft', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'published') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <x-input-error :messages="$errors->all()" class="md:col-span-2" />
                <div class="flex flex-wrap gap-3 md:col-span-2">
                    <button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Add category</button>
                    <a href="{{ route('organizer.tournaments.categories', $tournament) }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
