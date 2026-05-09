<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\User;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Services\RatingRecalculationService;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
use Modules\Tournaments\Services\TournamentDrawService;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Volt::route('s/{token}', 'scoresheets.show')->name('scoresheets.show');

Route::get('rankings', function () {
    $format = request('format', 'singles') === 'doubles' ? 'doubles' : 'singles';
    $ratingColumn = $format.'_rating';
    $matchesColumn = $format.'_matches';

    $players = PlayerProfile::query()
        ->with('user.clubs')
        ->where($matchesColumn, '>', 0)
        ->when(request('country'), fn ($query, $country) => $query->where('country', $country))
        ->when(request('state'), fn ($query, $state) => $query->where('state', $state))
        ->when(request('city'), fn ($query, $city) => $query->where('city', $city))
        ->when(request('club'), function ($query, $club) {
            $query->whereHas('user.clubs', fn ($clubs) => $clubs->where('slug', $club));
        })
        ->orderByDesc($ratingColumn)
        ->paginate(20)
        ->withQueryString();

    return view('rankings', [
        'format' => $format,
        'players' => $players,
        'clubs' => Club::orderBy('name')->get(),
    ]);
})->name('rankings');

Route::get('matches', fn () => view('matches.index', [
    'matches' => MatchRecord::with('players.user.playerProfile', 'club', 'tournament')
        ->whereJsonLength('score', '>', 0)
        ->when(request('format'), fn ($query, $format) => $query->where('format', $format))
        ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
        ->when(request('club'), fn ($query, $club) => $query->whereHas('club', fn ($clubs) => $clubs->where('slug', $club)))
        ->when(request('tournament'), fn ($query, $tournament) => $query->whereHas('tournament', fn ($tournaments) => $tournaments->where('slug', $tournament)))
        ->latest('played_at')
        ->paginate(12)
        ->withQueryString(),
    'clubs' => Club::orderBy('name')->get(),
    'tournaments' => Tournament::orderBy('name')->get(),
]))->name('matches.index');

Route::get('players/{playerProfile:slug}', fn (PlayerProfile $playerProfile) => view('players.show', [
    'player' => $playerProfile->load('user.clubs'),
]))->name('players.show');

Route::get('clubs', fn () => view('clubs.index', [
    'clubs' => Club::withCount('members')
        ->orderByDesc('members_count')
        ->orderBy('name')
        ->paginate(12),
]))->name('clubs.index');

Route::get('clubs/{club:slug}', fn (Club $club) => view('clubs.show', [
    'club' => $club->load('members.playerProfile'),
]))->name('clubs.show');

Route::get('tournaments', fn () => view('tournaments.index', [
    'tournaments' => Tournament::with('club')
        ->withCount(['matches', 'categories', 'entrants'])
        ->orderByRaw("case status when 'published' then 0 when 'draft' then 1 else 2 end")
        ->orderBy('starts_at')
        ->paginate(12),
]))->name('tournaments.index');

Route::get('tournaments/{tournament:slug}', fn (Tournament $tournament) => view('tournaments.show', [
    'tournament' => $tournament->load([
        'club',
        'organizer',
        'categories.entrants.players.user.playerProfile',
        'matches.players.user.playerProfile',
        'matches.tournamentCategory',
    ])->loadCount('matches', 'entrants'),
]))->name('tournaments.show');

Route::get('tournaments/{tournament:slug}/draws/{category:slug}', function (Tournament $tournament, TournamentCategory $category) {
    abort_unless($category->tournament_id === $tournament->id, 404);

    return view('tournaments.draw', [
        'tournament' => $tournament->load('club', 'organizer', 'categories'),
        'category' => $category->load('entrants.players.user.playerProfile', 'matches.players.user.playerProfile'),
    ]);
})->scopeBindings()->name('tournaments.draw');

Route::get('tournaments/{tournament:slug}/matches', function (Tournament $tournament) {
    $matches = $tournament->matches()
        ->with('players.user.playerProfile', 'tournamentCategory')
        ->when(request('date'), fn ($query, $date) => $query->whereDate('played_at', $date))
        ->orderByRaw("case when live_status = 'live' then 0 else 1 end")
        ->orderBy('scheduled_at')
        ->orderBy('played_at')
        ->orderBy('tournament_category_id')
        ->get();

    return view('tournaments.matches', [
        'tournament' => $tournament->load('club', 'organizer', 'categories'),
        'liveMatches' => $matches->where('live_status', 'live')->values(),
        'matches' => $matches
            ->reject(fn (MatchRecord $match) => $match->live_status === 'live')
            ->groupBy(fn (MatchRecord $match) => $match->played_at->toDateString()),
    ]);
})->name('tournaments.matches');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Volt::route('profile/player', 'profile.player-settings')->name('profile.player');
    Volt::route('matches/create', 'matches.create')->name('matches.create');
    Volt::route('matches/{match}/confirm', 'matches.confirm')->name('matches.confirm');

    Route::get('tournaments/{tournament:slug}/register', function (Tournament $tournament) {
        return view('tournaments.register', [
            'tournament' => $tournament->load('club', 'categories'),
        ]);
    })->name('tournaments.register.form');

    Route::post('tournaments/{tournament:slug}/register', function (Tournament $tournament) {
        abort_unless($tournament->registrationOpen(), 403);

        $data = request()->validate([
            'tournament_category_id' => ['required', 'exists:tournament_categories,id'],
            'contact_name' => ['required', 'string', 'max:160'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'identity_type' => ['required', 'in:ic,passport'],
            'identity_number' => ['required', 'string', 'max:80'],
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'partner_email' => ['nullable', 'email', 'exists:users,email'],
            'partner_name' => ['nullable', 'string', 'max:120'],
        ]);
        $category = TournamentCategory::where('tournament_id', $tournament->id)->findOrFail($data['tournament_category_id']);
        $partner = filled($data['partner_email'] ?? null) ? User::where('email', $data['partner_email'])->first() : null;
        $documentPath = request()->file('identity_document')->store('kyc/tournament-registrations');

        $entrant = TournamentEntrant::updateOrCreate([
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'created_by' => auth()->id(),
        ], [
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'created_by' => auth()->id(),
            'name' => $data['contact_name'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'identity_type' => $data['identity_type'],
            'identity_number' => $data['identity_number'],
            'identity_document_path' => $documentPath,
            'kyc_status' => 'pending',
            'status' => 'pending',
        ]);
        $entrant->players()->delete();
        $entrant->players()->create(['user_id' => auth()->id(), 'position' => 1]);

        if ($category->format !== 'singles') {
            $entrant->players()->create([
                'user_id' => $partner?->id,
                'display_name' => $partner ? null : ($data['partner_name'] ?? null),
                'position' => 2,
            ]);
        }

        return redirect()->route('tournaments.show', $tournament)->with('status', 'Registration and KYC submitted for organizer approval.');
    })->name('tournaments.register');

    Route::prefix('organizer/tournaments')->name('organizer.tournaments.')->scopeBindings()->group(function () {
        Route::get('/', fn () => view('organizer.tournaments.index', [
            'tournaments' => Tournament::withCount('categories', 'entrants', 'matches')
                ->when(! auth()->user()->hasRole('superadmin'), fn ($query) => $query->where('organizer_id', auth()->id()))
                ->latest()
                ->paginate(12),
        ]))->name('index');

        Route::get('create', fn () => view('organizer.tournaments.create', [
            'clubs' => Club::orderBy('name')->get(),
        ]))->name('create');

        Route::post('/', function () {
            $data = request()->validate([
                'club_id' => ['nullable', 'exists:clubs,id'],
                'name' => ['required', 'string', 'max:140'],
                'country' => ['nullable', 'string', 'max:80'],
                'state' => ['nullable', 'string', 'max:80'],
                'city' => ['nullable', 'string', 'max:80'],
                'venue' => ['nullable', 'string', 'max:160'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'status' => ['required', 'in:draft,published,archived'],
                'registration_mode' => ['required', 'in:public,private,invitation'],
                'registration_status' => ['required', 'in:open,closed'],
                'registration_deadline' => ['nullable', 'date'],
            ]);
            $tournament = Tournament::create([...$data, 'organizer_id' => auth()->id(), 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);

            return redirect()->route('organizer.tournaments.edit', $tournament)->with('status', 'Tournament created.');
        })->name('store');

        Route::get('{tournament:slug}/edit', fn (Tournament $tournament) => tap(null, function () use ($tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
        }) ?? view('organizer.tournaments.edit', [
            'tournament' => $tournament->load('categories'),
            'clubs' => Club::orderBy('name')->get(),
        ]))->name('edit');

        Route::patch('{tournament:slug}', function (Tournament $tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            $data = request()->validate([
                'club_id' => ['nullable', 'exists:clubs,id'],
                'name' => ['required', 'string', 'max:140'],
                'country' => ['nullable', 'string', 'max:80'],
                'state' => ['nullable', 'string', 'max:80'],
                'city' => ['nullable', 'string', 'max:80'],
                'venue' => ['nullable', 'string', 'max:160'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
                'status' => ['required', 'in:draft,published,archived'],
                'registration_mode' => ['required', 'in:public,private,invitation'],
                'registration_status' => ['required', 'in:open,closed'],
                'registration_deadline' => ['nullable', 'date'],
            ]);
            $tournament->update($data);

            return back()->with('status', 'Tournament updated.');
        })->name('update');

        Route::post('{tournament:slug}/categories', function (Tournament $tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            $data = request()->validate([
                'name' => ['required', 'string', 'max:120'],
                'format' => ['required', 'in:singles,doubles,mixed'],
                'level_label' => ['nullable', 'string', 'max:80'],
                'draw_mode' => ['required', 'in:single_elimination,round_robin'],
                'max_entrants' => ['nullable', 'integer', 'min:2', 'max:256'],
                'status' => ['required', 'in:draft,published,closed'],
            ]);
            $tournament->categories()->create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(3))]);

            return back()->with('status', 'Category added.');
        })->name('categories.store');

        Route::get('{tournament:slug}/registrations', fn (Tournament $tournament) => tap(null, function () use ($tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
        }) ?? view('organizer.tournaments.registrations', [
            'tournament' => $tournament->load('categories.entrants.players.user.playerProfile'),
            'users' => User::with('playerProfile')->orderBy('name')->get(),
        ]))->name('registrations');

        Route::post('{tournament:slug}/entrants', function (Tournament $tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            $data = request()->validate([
                'tournament_category_id' => ['required', 'exists:tournament_categories,id'],
                'player_one_id' => ['nullable', 'exists:users,id'],
                'player_two_id' => ['nullable', 'exists:users,id'],
                'player_one_name' => ['nullable', 'string', 'max:120'],
                'player_two_name' => ['nullable', 'string', 'max:120'],
                'status' => ['required', 'in:pending,approved,rejected,withdrawn'],
                'seed' => ['nullable', 'integer', 'min:1'],
            ]);
            $category = TournamentCategory::where('tournament_id', $tournament->id)->findOrFail($data['tournament_category_id']);
            $entrant = $tournament->entrants()->create([
                'tournament_category_id' => $category->id,
                'created_by' => auth()->id(),
                'status' => $data['status'],
                'seed' => $data['seed'] ?? null,
            ]);
            $entrant->players()->create(['user_id' => $data['player_one_id'] ?? null, 'display_name' => $data['player_one_name'] ?? null, 'position' => 1]);
            if ($category->format !== 'singles') {
                $entrant->players()->create(['user_id' => $data['player_two_id'] ?? null, 'display_name' => $data['player_two_name'] ?? null, 'position' => 2]);
            }

            return back()->with('status', 'Entrant added.');
        })->name('entrants.store');

        Route::patch('{tournament:slug}/entrants/{entrant}', function (Tournament $tournament, TournamentEntrant $entrant) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            abort_unless($entrant->tournament_id === $tournament->id, 404);
            $data = request()->validate([
                'status' => ['required', 'in:pending,approved,rejected,withdrawn'],
                'seed' => ['nullable', 'integer', 'min:1'],
            ]);
            $entrant->update($data);

            return back()->with('status', 'Entrant updated.');
        })->name('entrants.update');

        Route::get('{tournament:slug}/entrants/{entrant}/document', function (Tournament $tournament, TournamentEntrant $entrant) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            abort_unless($entrant->tournament_id === $tournament->id, 404);
            abort_unless(filled($entrant->identity_document_path) && Storage::disk('local')->exists($entrant->identity_document_path), 404);

            return Storage::disk('local')->download($entrant->identity_document_path);
        })->name('entrants.document');

        Route::get('{tournament:slug}/draws', fn (Tournament $tournament) => tap(null, function () use ($tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
        }) ?? view('organizer.tournaments.draws', [
            'tournament' => $tournament->load('categories.entrants.players.user.playerProfile', 'categories.matches.players.user.playerProfile'),
        ]))->name('draws');

        Route::post('{tournament:slug}/draws/generate', function (Tournament $tournament, TournamentDrawService $draws) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            $data = request()->validate([
                'category_ids' => ['required', 'array', 'min:1'],
                'category_ids.*' => ['integer', 'exists:tournament_categories,id'],
                'courts_count' => ['nullable', 'integer', 'min:1', 'max:50'],
                'court_label_prefix' => ['nullable', 'string', 'max:40'],
                'first_court_number' => ['nullable', 'integer', 'min:1', 'max:99'],
                'schedule_start_time' => ['nullable', 'date_format:H:i'],
                'match_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            ]);
            $created = $draws->generateTournament($tournament, $data['category_ids'], $data);

            return back()->with('status', "{$created} draw matches generated across selected categories.");
        })->name('draws.generate.tournament');

        Route::post('{tournament:slug}/draws/{category:slug}/generate', function (Tournament $tournament, TournamentCategory $category, TournamentDrawService $draws) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            abort_unless($category->tournament_id === $tournament->id, 404);
            $schedule = request()->validate([
                'courts_count' => ['nullable', 'integer', 'min:1', 'max:50'],
                'court_label_prefix' => ['nullable', 'string', 'max:40'],
                'first_court_number' => ['nullable', 'integer', 'min:1', 'max:99'],
                'schedule_start_time' => ['nullable', 'date_format:H:i'],
                'match_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            ]);
            $created = $draws->generate($category, $schedule);

            return back()->with('status', "{$created} draw matches generated.");
        })->name('draws.generate');

        Route::get('{tournament:slug}/draws/{category:slug}/generate', function (Tournament $tournament, TournamentCategory $category) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            abort_unless($category->tournament_id === $tournament->id, 404);

            return redirect()
                ->route('organizer.tournaments.draws', $tournament)
                ->withErrors(['draw' => 'Use the Generate draw button to create matches for '.$category->name.'.']);
        })->name('draws.generate.notice');

        Route::get('{tournament:slug}/matches', fn (Tournament $tournament) => tap(null, function () use ($tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
        }) ?? view('organizer.tournaments.matches', [
            'tournament' => $tournament->load('categories'),
            'matches' => $tournament->matches()->with('players.user.playerProfile', 'tournamentCategory')->orderBy('scheduled_at')->orderBy('played_at')->paginate(20),
        ]))->name('matches');

        Route::patch('{tournament:slug}/matches/{match}/result', function (Tournament $tournament, MatchRecord $match, RatingService $ratings, MatchScoreService $scores) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            abort_unless($match->tournament_id === $tournament->id, 404);

            if ($match->status === 'confirmed') {
                throw ValidationException::withMessages([
                    'result' => 'Confirmed match results cannot be edited from this page.',
                ]);
            }

            $data = request()->validate([
                'played_at' => ['required', 'date'],
                'winner_side' => ['required', 'in:A,B'],
                'games' => ['required', 'array'],
                'games.*.a' => ['nullable', 'integer', 'min:0', 'max:30'],
                'games.*.b' => ['nullable', 'integer', 'min:0', 'max:30'],
            ]);

            $result = $scores->validateScoreRows($data['games'], $data['winner_side']);

            $match->forceFill([
                'played_at' => $data['played_at'],
                'score' => $result['score'],
                'winner_side' => $result['winner_side'],
                'status' => 'pending_confirmation',
                'live_status' => 'approved',
                'live_score' => [
                    'current_game' => count($result['score']) + 1,
                    'current' => ['a' => 0, 'b' => 0],
                    'games' => $result['score'],
                    'history' => [],
                ],
            ])->save();

            $ratings->confirmAsAdmin($match->fresh());

            return back()->with('status', 'Tournament match result saved and ratings updated.');
        })->name('matches.result');

        Route::patch('{tournament:slug}/matches/{match}/approve-live-score', function (Tournament $tournament, MatchRecord $match, RatingService $ratings, MatchScoreService $scores) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            abort_unless($match->tournament_id === $tournament->id, 404);
            abort_unless($match->live_status === 'submitted', 404);

            $scores->approveSubmittedScore($match);
            $ratings->confirmAsAdmin($match->fresh());

            return back()->with('status', 'Live scoresheet approved and ratings updated.');
        })->name('matches.approve-live-score');
    });
});

Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('admin.dashboard', [
        'usersCount' => User::count(),
        'clubsCount' => Club::count(),
        'tournamentsCount' => Tournament::count(),
        'pendingMatchesCount' => MatchRecord::whereIn('status', ['pending_confirmation', 'disputed'])->count(),
        'activeAlgorithm' => RatingAlgorithm::active(),
    ]))->name('dashboard');

    Route::get('users', fn () => view('admin.users', [
        'users' => User::with('playerProfile', 'clubs', 'roles')->latest()->paginate(20),
    ]))->name('users');

    Route::patch('users/{user}/superadmin', function (User $user) {
        $role = Role::findOrCreate('superadmin', 'web');
        request()->boolean('enabled') ? $user->assignRole($role) : $user->removeRole($role);

        return back()->with('status', 'User role updated.');
    })->name('users.superadmin');

    Route::patch('users/{user}/suspension', function (User $user) {
        $user->forceFill(['suspended_at' => request()->boolean('suspended') ? now() : null])->save();

        return back()->with('status', 'User suspension updated.');
    })->name('users.suspension');

    Route::get('clubs', fn () => view('admin.clubs', [
        'clubs' => Club::with('members.playerProfile')->latest()->paginate(20),
    ]))->name('clubs');

    Route::post('clubs', function () {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        Club::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);

        return back()->with('status', 'Club created.');
    })->name('clubs.store');

    Route::patch('clubs/{club}', function (Club $club) {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $club->update($data);

        return back()->with('status', 'Club updated.');
    })->name('clubs.update');

    Route::delete('clubs/{club}', function (Club $club) {
        $club->delete();

        return back()->with('status', 'Club deleted.');
    })->name('clubs.destroy');

    Route::post('clubs/{club}/members', function (Club $club) {
        $data = request()->validate(['email' => ['required', 'email', 'exists:users,email']]);
        $club->members()->syncWithoutDetaching([User::where('email', $data['email'])->value('id')]);

        return back()->with('status', 'Club member added.');
    })->name('clubs.members.store');

    Route::delete('clubs/{club}/members/{user}', function (Club $club, User $user) {
        $club->members()->detach($user);

        return back()->with('status', 'Club member removed.');
    })->name('clubs.members.destroy');

    Route::get('tournaments', fn () => view('admin.tournaments', [
        'tournaments' => Tournament::with('club', 'matches')->latest()->paginate(20),
        'clubs' => Club::orderBy('name')->get(),
    ]))->name('tournaments');

    Route::post('tournaments', function () {
        $data = request()->validate([
            'club_id' => ['nullable', 'exists:clubs,id'],
            'name' => ['required', 'string', 'max:140'],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);
        Tournament::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4))]);

        return back()->with('status', 'Tournament created.');
    })->name('tournaments.store');

    Route::patch('tournaments/{tournament}', function (Tournament $tournament) {
        $data = request()->validate([
            'club_id' => ['nullable', 'exists:clubs,id'],
            'name' => ['required', 'string', 'max:140'],
            'country' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);
        $tournament->update($data);

        return back()->with('status', 'Tournament updated.');
    })->name('tournaments.update');

    Route::get('matches', fn () => view('admin.matches', [
        'matches' => MatchRecord::with('players.user.playerProfile', 'club', 'tournament')
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString(),
        'clubs' => Club::orderBy('name')->get(),
        'tournaments' => Tournament::orderBy('name')->get(),
    ]))->name('matches');

    Route::patch('matches/{match}/confirm', function (MatchRecord $match, RatingService $ratings) {
        $ratings->confirmAsAdmin($match);

        return back()->with('status', 'Match confirmed.');
    })->name('matches.confirm');

    Route::patch('matches/{match}/void', function (MatchRecord $match) {
        $match->forceFill(['status' => 'void'])->save();

        return back()->with('status', 'Match voided.');
    })->name('matches.void');

    Route::patch('matches/{match}', function (MatchRecord $match, RatingService $ratings) {
        $data = request()->validate([
            'club_id' => ['nullable', 'exists:clubs,id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'played_at' => ['required', 'date'],
            'winner_side' => ['required', 'in:A,B'],
            'status' => ['required', 'in:pending_confirmation,confirmed,disputed,void'],
        ]);

        $match->update($data);

        if ($data['status'] === 'confirmed') {
            $ratings->confirmAsAdmin($match->refresh());
        }

        return back()->with('status', 'Match updated.');
    })->name('matches.update');

    Route::get('algorithms', fn (RatingRecalculationService $recalc) => view('admin.algorithms', [
        'algorithms' => RatingAlgorithm::latest()->get(),
        'activeAlgorithm' => RatingAlgorithm::active(),
        'preview' => session('recalc_preview'),
    ]))->name('algorithms');

    Route::post('algorithms', function () {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'version' => ['required', 'string', 'max:40', 'unique:rating_algorithms,version'],
            'settings.starting_rating' => ['required', 'numeric'],
            'settings.min_rating' => ['required', 'numeric'],
            'settings.max_rating' => ['required', 'numeric'],
            'settings.base_delta' => ['required', 'numeric'],
            'settings.margin_weight' => ['required', 'numeric'],
            'settings.max_margin_bonus' => ['required', 'integer'],
            'settings.rating_scale_divisor' => ['required', 'numeric'],
        ]);
        RatingAlgorithm::create([
            'created_by' => auth()->id(),
            'name' => $data['name'],
            'version' => $data['version'],
            'status' => 'draft',
            'settings' => $data['settings'],
        ]);

        return back()->with('status', 'Algorithm draft created.');
    })->name('algorithms.store');

    Route::patch('algorithms/{algorithm}', function (RatingAlgorithm $algorithm) {
        abort_if($algorithm->status === 'active', 422, 'Active algorithms cannot be edited. Create a new draft version instead.');

        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'version' => ['required', 'string', 'max:40', 'unique:rating_algorithms,version,'.$algorithm->id],
            'settings.starting_rating' => ['required', 'numeric'],
            'settings.min_rating' => ['required', 'numeric'],
            'settings.max_rating' => ['required', 'numeric'],
            'settings.base_delta' => ['required', 'numeric'],
            'settings.margin_weight' => ['required', 'numeric'],
            'settings.max_margin_bonus' => ['required', 'integer'],
            'settings.rating_scale_divisor' => ['required', 'numeric'],
        ]);

        $algorithm->update([
            'name' => $data['name'],
            'version' => $data['version'],
            'settings' => $data['settings'],
        ]);

        return back()->with('status', 'Algorithm draft updated.');
    })->name('algorithms.update');

    Route::patch('algorithms/{algorithm}/activate', function (RatingAlgorithm $algorithm) {
        RatingAlgorithm::where('status', 'active')->update(['status' => 'archived']);
        $algorithm->forceFill(['status' => 'active', 'activated_at' => now()])->save();

        return back()->with('status', 'Algorithm activated.');
    })->name('algorithms.activate');

    Route::patch('algorithms/{algorithm}/archive', function (RatingAlgorithm $algorithm) {
        if ($algorithm->status !== 'active') {
            $algorithm->forceFill(['status' => 'archived'])->save();
        }

        return back()->with('status', 'Algorithm archived.');
    })->name('algorithms.archive');

    Route::post('algorithms/{algorithm}/recalculate/preview', fn (RatingAlgorithm $algorithm, RatingRecalculationService $recalc) => back()->with('recalc_preview', $recalc->preview($algorithm)))->name('algorithms.recalculate.preview');
    Route::post('algorithms/{algorithm}/recalculate/apply', fn (RatingAlgorithm $algorithm, RatingRecalculationService $recalc) => back()->with('recalc_preview', $recalc->apply($algorithm))->with('status', 'Ratings recalculated.'))->name('algorithms.recalculate.apply');
});

require __DIR__.'/auth.php';
