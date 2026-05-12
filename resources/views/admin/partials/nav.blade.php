<div class="mb-6 flex flex-wrap gap-2">
    @foreach ([
        'admin.dashboard' => 'Overview',
        'admin.users' => 'Users',
        'admin.clubs' => 'Clubs',
        'admin.tournaments' => 'Tournaments',
        'admin.matches' => 'Matches',
        'admin.algorithms' => 'Algorithms',
    ] as $route => $label)
        <a href="{{ route($route) }}" class="rounded-full px-4 py-2 text-sm font-black uppercase {{ request()->routeIs($route) ? 'bg-brand-blue text-white' : 'bg-white text-brand-blue' }}">{{ $label }}</a>
    @endforeach
</div>

@if (session('status'))
    <div class="mb-6 rounded-md bg-green-50 p-4 font-bold text-green-700">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
