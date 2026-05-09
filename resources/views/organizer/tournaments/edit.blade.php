<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">{{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif

        <form method="POST" action="{{ route('organizer.tournaments.update', $tournament) }}" class="mb-8 grid gap-4 rounded-lg bg-white p-6 shadow-lg md:grid-cols-2">@csrf @method('PATCH')
            @include('organizer.tournaments.partials.form', ['tournament' => $tournament])
            <button class="rounded-md bg-[#071a80] px-4 py-3 text-sm font-black uppercase text-white md:col-span-2">Save settings</button>
        </form>

        <section id="categories" class="rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-2xl font-black text-[#071a80]">Categories</h2>
            <form method="POST" action="{{ route('organizer.tournaments.categories.store', $tournament) }}" class="mt-5 grid gap-3 md:grid-cols-7">@csrf
                <input name="name" placeholder="Category name" class="rounded-md border-gray-300 md:col-span-2">
                <select name="format" class="rounded-md border-gray-300"><option value="singles">Singles</option><option value="doubles">Doubles</option><option value="mixed">Mixed</option></select>
                <input name="level_label" placeholder="Level label" class="rounded-md border-gray-300">
                <select name="draw_mode" class="rounded-md border-gray-300"><option value="single_elimination">Single elimination</option><option value="round_robin">Round robin</option></select>
                <select name="group_size" class="rounded-md border-gray-300"><option value="4">Group of 4</option><option value="3">Group of 3</option><option value="5">Group of 5</option><option value="6">Group of 6</option></select>
                <input name="max_entrants" type="number" min="2" placeholder="Max" class="rounded-md border-gray-300">
                <select name="status" class="rounded-md border-gray-300"><option value="published">Published</option><option value="draft">Draft</option><option value="closed">Closed</option></select>
                <button class="rounded-md bg-[#071a80] px-4 py-2 text-sm font-black uppercase text-white md:col-span-7">Add category</button>
            </form>
            <div class="mt-6 grid gap-3 md:grid-cols-2">
                @foreach ($tournament->categories as $category)
                    <div class="rounded-md border border-blue-950/10 p-4">
                        <h3 class="font-black text-[#071a80]">{{ $category->name }}</h3>
                        <p class="text-sm text-blue-950/60">{{ $category->format }} | {{ str_replace('_', ' ', $category->draw_mode) }}{{ $category->draw_mode === 'round_robin' ? ' | groups of '.$category->group_size : '' }} | {{ $category->status }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
