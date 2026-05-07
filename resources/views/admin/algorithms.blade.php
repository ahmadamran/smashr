<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Rating algorithms</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        @if ($preview)
            <div class="mb-6 rounded-lg bg-blue-50 p-5 text-[#071a80]">
                <h2 class="font-black">Recalculation preview</h2>
                <p>{{ $preview['message'] }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.algorithms.store') }}" class="mb-6 rounded-lg bg-white p-5 shadow">@csrf
            <h2 class="mb-4 text-xl font-black text-[#071a80]">Create draft version</h2>
            <div class="grid gap-3 md:grid-cols-4">
                <input name="name" placeholder="Name" class="rounded-md border-gray-300">
                <input name="version" placeholder="v2" class="rounded-md border-gray-300">
                @foreach (\Modules\Ratings\Models\RatingAlgorithm::DEFAULT_SETTINGS as $key => $value)
                    <label class="text-xs font-bold uppercase text-blue-950/60">{{ str_replace('_', ' ', $key) }}
                        <input name="settings[{{ $key }}]" value="{{ $value }}" class="mt-1 block w-full rounded-md border-gray-300 text-base font-normal normal-case text-black">
                    </label>
                @endforeach
            </div>
            <button class="mt-4 rounded-md bg-[#071a80] px-4 py-2 font-black uppercase text-white">Create draft</button>
        </form>

        <div class="grid gap-5">
            @foreach ($algorithms as $algorithm)
                <section class="rounded-lg bg-white p-5 shadow">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-[#d6a31d]">{{ $algorithm->status }}</p>
                            <h2 class="text-2xl font-black text-[#071a80]">{{ $algorithm->name }} {{ $algorithm->version }}</h2>
                            <p class="text-sm text-blue-950/60">Activated: {{ $algorithm->activated_at?->toDateTimeString() ?? 'Not active' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($algorithm->status !== 'active')
                                <form method="POST" action="{{ route('admin.algorithms.activate', $algorithm) }}">@csrf @method('PATCH')<button class="rounded bg-green-600 px-3 py-2 text-xs font-bold text-white">Activate</button></form>
                                <form method="POST" action="{{ route('admin.algorithms.archive', $algorithm) }}">@csrf @method('PATCH')<button class="rounded border px-3 py-2 text-xs font-bold">Archive</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.algorithms.recalculate.preview', $algorithm) }}">@csrf<button class="rounded border px-3 py-2 text-xs font-bold">Preview recalc</button></form>
                            <form method="POST" action="{{ route('admin.algorithms.recalculate.apply', $algorithm) }}">@csrf<button class="rounded bg-red-600 px-3 py-2 text-xs font-bold text-white">Apply recalc</button></form>
                        </div>
                    </div>
                    <dl class="mt-4 grid gap-2 md:grid-cols-4">
                        @foreach ($algorithm->settings as $key => $value)
                            <div class="rounded bg-[#f3f6fb] p-3"><dt class="text-xs font-bold uppercase text-blue-950/50">{{ str_replace('_', ' ', $key) }}</dt><dd class="font-black">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($algorithm->status !== 'active')
                        <form method="POST" action="{{ route('admin.algorithms.update', $algorithm) }}" class="mt-5 grid gap-3 rounded-md border border-blue-950/10 p-4 md:grid-cols-4">@csrf @method('PATCH')
                            <input name="name" value="{{ $algorithm->name }}" class="rounded-md border-gray-300">
                            <input name="version" value="{{ $algorithm->version }}" class="rounded-md border-gray-300">
                            @foreach (\Modules\Ratings\Models\RatingAlgorithm::DEFAULT_SETTINGS as $key => $value)
                                <label class="text-xs font-bold uppercase text-blue-950/60">{{ str_replace('_', ' ', $key) }}
                                    <input name="settings[{{ $key }}]" value="{{ $algorithm->settings[$key] ?? $value }}" class="mt-1 block w-full rounded-md border-gray-300 text-base font-normal normal-case text-black">
                                </label>
                            @endforeach
                            <button class="rounded-md border px-4 py-2 font-bold md:col-span-4">Save draft settings</button>
                        </form>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
