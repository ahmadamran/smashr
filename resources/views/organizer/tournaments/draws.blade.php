<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Draws | {{ $tournament->name }}</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
        @if (session('status')) <div class="mb-6 rounded bg-green-50 p-4 font-bold text-green-800">{{ session('status') }}</div> @endif
        @if ($errors->any())
            <div class="mb-6 rounded bg-red-50 p-4 font-bold text-red-800">{{ $errors->first() }}</div>
        @endif

        @php($generatableCategories = $tournament->categories->filter(fn ($category) => $category->entrants->where('status', 'approved')->count() >= 2))
        <section class="mb-6 rounded-lg bg-white p-6 shadow-lg">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">Tournament schedule</p>
                    <h2 class="text-2xl font-black text-[#071a80]">Generate draws across categories</h2>
                    <p class="mt-1 text-sm font-bold text-blue-950/50">Selected categories share one court and time schedule.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('organizer.tournaments.draws.generate.tournament', $tournament) }}" class="mt-5">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="text-xs font-black uppercase text-[#071a80]">
                        Courts
                        <input type="number" name="courts_count" min="1" max="50" value="{{ old('courts_count', 2) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    </label>
                    <label class="text-xs font-black uppercase text-[#071a80]">
                        Court label
                        <input type="text" name="court_label_prefix" value="{{ old('court_label_prefix', 'Court') }}" placeholder="Court" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    </label>
                    <label class="text-xs font-black uppercase text-[#071a80]">
                        First court
                        <input type="number" name="first_court_number" min="1" max="99" value="{{ old('first_court_number', 1) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    </label>
                    <label class="text-xs font-black uppercase text-[#071a80]">
                        Start time
                        <input type="time" name="schedule_start_time" value="{{ old('schedule_start_time', '09:00') }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    </label>
                    <label class="text-xs font-black uppercase text-[#071a80]">
                        Minutes
                        <input type="number" name="match_duration_minutes" min="5" max="240" step="5" value="{{ old('match_duration_minutes', 30) }}" class="mt-1 w-full rounded-md border-blue-950/10 text-sm font-bold text-blue-950">
                    </label>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tournament->categories as $category)
                        @php($approvedEntrants = $category->entrants->where('status', 'approved'))
                        <label class="flex items-start gap-3 rounded-md border border-blue-950/10 p-3 text-sm font-bold text-blue-950/70">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(old('category_ids') ? in_array($category->id, old('category_ids', [])) : $approvedEntrants->count() >= 2) @disabled($approvedEntrants->count() < 2) class="mt-1 rounded border-blue-950/20 text-[#071a80] focus:ring-[#071a80]">
                            <span>
                                <span class="block font-black text-[#071a80]">{{ $category->name }}</span>
                                <span class="block text-xs uppercase text-blue-950/45">{{ $approvedEntrants->count() }} approved entrants | {{ str_replace('_', ' ', $category->draw_mode) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <button @disabled($generatableCategories->isEmpty()) class="mt-5 rounded-md bg-[#071a80] px-5 py-3 text-xs font-black uppercase text-white disabled:cursor-not-allowed disabled:bg-blue-950/30">Generate selected draws and schedule</button>
            </form>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($tournament->categories as $category)
                @php($approvedEntrants = $category->entrants->where('status', 'approved'))
                <section class="rounded-lg bg-white p-6 shadow-lg">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#d6a31d]">{{ str_replace('_', ' ', $category->draw_mode) }}</p>
                            <h2 class="text-2xl font-black text-[#071a80]">{{ $category->name }}</h2>
                            <p class="mt-1 text-sm font-bold text-blue-950/50">{{ $approvedEntrants->count() }} approved entrants | {{ $category->matches->count() }} matches</p>
                        </div>
                    </div>
                    <div class="mt-5 divide-y divide-blue-950/10">
                        @foreach ($approvedEntrants->sortBy('seed') as $entrant)
                            <p class="py-2 text-sm font-bold text-blue-950/70">{{ $entrant->seed ? '#'.$entrant->seed.' ' : '' }}{{ $entrant->displayName() }}</p>
                        @endforeach
                    </div>
                    <a href="{{ route('tournaments.draw', [$tournament, $category]) }}" class="mt-5 inline-flex rounded-md border border-blue-950/10 px-4 py-2 text-xs font-black uppercase text-[#071a80]">View public draw</a>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
