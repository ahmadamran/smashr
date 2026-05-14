<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">Add entrant | {{ $tournament->name }}</h1></x-slot>

    <div class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        @include('organizer.tournaments.partials.nav', ['tournament' => $tournament])
    </div>

    <div class="mx-auto max-w-4xl px-4 pb-10 sm:px-6 lg:px-8">
        <section class="rounded-lg bg-white p-6 shadow-lg">
            <div>
                <p class="text-xs font-black uppercase tracking-[.25em] text-brand-green">Registration control</p>
                <h2 class="mt-1 text-2xl font-black text-brand-blue">Add entrant</h2>
                <p class="mt-2 text-sm font-bold text-brand-ink/60">Add a player or pair manually to one tournament category.</p>
            </div>

            <form method="POST" action="{{ route('organizer.tournaments.entrants.store', $tournament) }}" class="mt-6 grid gap-4 md:grid-cols-2">@csrf
                <label class="font-bold text-brand-blue md:col-span-2">Category
                    <select name="tournament_category_id" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach ($tournament->categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('tournament_category_id') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="font-bold text-brand-blue" x-data="playerSearch(@js(route('organizer.tournaments.entrants.user-search', $tournament)), @js(old('player_one_id')), 'Search player 1')" x-init="init()">
                    <label>Player 1 user</label>
                    <input type="hidden" name="player_one_id" :value="selected?.id || ''">
                    <div class="relative mt-1">
                        <input type="search" x-model="query" @input.debounce.250ms="search" @focus="open = results.length > 0" placeholder="Search player 1" class="w-full rounded-md border-gray-300 pr-20">
                        <button x-show="selected" type="button" @click="clear" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">Clear</button>
                        <div x-show="open" @click.outside="open = false" class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-md border border-brand-ink/10 bg-white shadow-xl" style="display: none;">
                            <template x-for="user in results" :key="user.id">
                                <button type="button" @click="select(user)" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-brand-surface">
                                    <span>
                                        <span class="block font-black text-brand-blue" x-text="user.name"></span>
                                        <span class="block text-xs font-bold text-brand-ink/50" x-text="user.meta"></span>
                                    </span>
                                    <span class="flex shrink-0 gap-2">
                                        <span class="rounded-md bg-brand-surface px-2 py-1 text-[11px] font-black uppercase text-brand-blue" x-text="ratingLabel(user, 'singles')"></span>
                                        <span class="rounded-md bg-brand-surface px-2 py-1 text-[11px] font-black uppercase text-brand-blue" x-text="ratingLabel(user, 'doubles')"></span>
                                        <span class="rounded-md bg-brand-surface px-2 py-1 text-[11px] font-black uppercase text-brand-blue" x-text="ratingLabel(user, 'mixed')"></span>
                                    </span>
                                </button>
                            </template>
                            <p x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-3 text-sm font-bold text-brand-ink/50">No players found.</p>
                            <p x-show="loading" class="px-4 py-3 text-sm font-bold text-brand-ink/50">Searching...</p>
                        </div>
                    </div>
                    <p class="mt-1 text-xs font-bold text-brand-ink/50" x-text="selected ? 'Linked to ' + selected.name + ' | ' + ratingLabel(selected, 'singles') + ' | ' + ratingLabel(selected, 'doubles') + ' | ' + ratingLabel(selected, 'mixed') : 'No linked user selected'"></p>
                </div>
                <div class="font-bold text-brand-blue" x-data="playerSearch(@js(route('organizer.tournaments.entrants.user-search', $tournament)), @js(old('player_two_id')), 'Search player 2')" x-init="init()">
                    <label>Player 2 user</label>
                    <input type="hidden" name="player_two_id" :value="selected?.id || ''">
                    <div class="relative mt-1">
                        <input type="search" x-model="query" @input.debounce.250ms="search" @focus="open = results.length > 0" placeholder="Search player 2" class="w-full rounded-md border-gray-300 pr-20">
                        <button x-show="selected" type="button" @click="clear" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md bg-brand-surface px-3 py-1 text-xs font-black uppercase text-brand-blue">Clear</button>
                        <div x-show="open" @click.outside="open = false" class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-md border border-brand-ink/10 bg-white shadow-xl" style="display: none;">
                            <template x-for="user in results" :key="user.id">
                                <button type="button" @click="select(user)" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-brand-surface">
                                    <span>
                                        <span class="block font-black text-brand-blue" x-text="user.name"></span>
                                        <span class="block text-xs font-bold text-brand-ink/50" x-text="user.meta"></span>
                                    </span>
                                    <span class="flex shrink-0 gap-2">
                                        <span class="rounded-md bg-brand-surface px-2 py-1 text-[11px] font-black uppercase text-brand-blue" x-text="ratingLabel(user, 'singles')"></span>
                                        <span class="rounded-md bg-brand-surface px-2 py-1 text-[11px] font-black uppercase text-brand-blue" x-text="ratingLabel(user, 'doubles')"></span>
                                        <span class="rounded-md bg-brand-surface px-2 py-1 text-[11px] font-black uppercase text-brand-blue" x-text="ratingLabel(user, 'mixed')"></span>
                                    </span>
                                </button>
                            </template>
                            <p x-show="query.length >= 2 && results.length === 0 && !loading" class="px-4 py-3 text-sm font-bold text-brand-ink/50">No players found.</p>
                            <p x-show="loading" class="px-4 py-3 text-sm font-bold text-brand-ink/50">Searching...</p>
                        </div>
                    </div>
                    <p class="mt-1 text-xs font-bold text-brand-ink/50" x-text="selected ? 'Linked to ' + selected.name + ' | ' + ratingLabel(selected, 'singles') + ' | ' + ratingLabel(selected, 'doubles') + ' | ' + ratingLabel(selected, 'mixed') : 'No linked user selected'"></p>
                </div>
                <label class="font-bold text-brand-blue">Player 1 name
                    <input name="player_one_name" value="{{ old('player_one_name') }}" placeholder="Player 1 name" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="font-bold text-brand-blue">Player 2 name
                    <input name="player_two_name" value="{{ old('player_two_name') }}" placeholder="Player 2 name" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="font-bold text-brand-blue">Status
                    <select name="status" class="mt-1 w-full rounded-md border-gray-300">
                        @foreach (['approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'approved') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-bold text-brand-blue">Seed
                    <input name="seed" type="number" min="1" value="{{ old('seed') }}" placeholder="Seed" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <x-input-error :messages="$errors->all()" class="md:col-span-2" />
                <div class="flex flex-wrap gap-3 md:col-span-2">
                    <button class="rounded-md bg-brand-blue px-5 py-3 text-xs font-black uppercase text-white">Add entrant</button>
                    <a href="{{ route('organizer.tournaments.registrations', $tournament) }}" class="rounded-md border border-brand-ink/10 px-5 py-3 text-xs font-black uppercase text-brand-blue">Cancel</a>
                </div>
            </form>
        </section>
    </div>
    <script>
        function playerSearch(url, selectedId, placeholder) {
            return {
                query: '',
                results: [],
                selected: null,
                loading: false,
                open: false,
                async init() {
                    if (!selectedId) {
                        this.query = '';
                        return;
                    }

                    const response = await fetch(`${url}?id=${selectedId}`, { headers: { Accept: 'application/json' } });
                    const users = await response.json();
                    this.select(users[0] || null);
                },
                async search() {
                    this.selected = null;
                    if (this.query.trim().length < 2) {
                        this.results = [];
                        this.open = false;
                        return;
                    }

                    this.loading = true;
                    const response = await fetch(`${url}?q=${encodeURIComponent(this.query.trim())}`, { headers: { Accept: 'application/json' } });
                    this.results = await response.json();
                    this.loading = false;
                    this.open = true;
                },
                select(user) {
                    this.selected = user;
                    this.query = user?.name || '';
                    this.results = [];
                    this.open = false;
                },
                clear() {
                    this.selected = null;
                    this.query = '';
                    this.results = [];
                    this.open = false;
                },
                ratingLabel(user, format) {
                    const value = user?.[format];
                    const label = { doubles: 'D', mixed: 'M', singles: 'S' }[format] || 'R';

                    return `${label} ${value ? Number(value).toFixed(3) : 'NR'}`;
                },
            };
        }
    </script>
</x-app-layout>
