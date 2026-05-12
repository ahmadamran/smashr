<?php

use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AlgorithmAdminService;
use App\Services\Admin\ClubAdminService;
use App\Services\Admin\MatchAdminService;
use App\Services\Admin\SmashrPointsService;
use App\Services\Admin\TournamentAdminService;
use App\Services\Admin\UserAdminService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Matches\Services\MatchScoreService;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Models\RatingEvent;
use Modules\Ratings\Services\RatingRecalculationService;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\DrawEngine\Enums\DrawType;
use Modules\Tournaments\DrawEngine\Services\DrawGeneratorService;
use Modules\Tournaments\DrawEngine\Services\DrawMatchPersistenceService;
use Modules\Tournaments\DrawEngine\Services\ScheduleGeneratorService;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
use Modules\Tournaments\Services\RoundRobinStandingsService;
use Modules\Tournaments\Services\TournamentDrawService;

Route::view('/', 'welcome');

Volt::route('s/{token}', 'scoresheets.show')->name('scoresheets.show');

Route::get('rankings', function () {
    $format = request('format', 'singles') === 'doubles' ? 'doubles' : 'singles';
    $gender = in_array(request('gender'), ['male', 'female'], true) ? request('gender') : null;
    $ratingColumn = $format.'_rating';
    $matchesColumn = $format.'_matches';

    $players = PlayerProfile::query()
        ->with('user.clubs')
        ->where($matchesColumn, '>', 0)
        ->when($gender, fn ($query, $gender) => $query->where('gender', $gender))
        ->when(request('search'), function ($query, $search) {
            $search = trim((string) $search);

            if ($search === '') {
                return;
            }

            $query->where(function ($query) use ($search) {
                $query->where('display_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%"));
            });
        })
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
        'gender' => $gender,
        'players' => $players,
        'clubs' => Club::orderBy('name')->get(),
    ]);
})->name('rankings');

Route::get('matches', fn () => view('matches.index', [
    'matches' => MatchRecord::with('players.user.playerProfile', 'club', 'tournament')
        ->whereJsonLength('score', '>', 0)
        ->when(request('search'), function ($query, $search) {
            $search = trim((string) $search);

            if ($search === '') {
                return;
            }

            $query->where(function ($query) use ($search) {
                $query
                    ->whereHas('players.user', fn ($users) => $users
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('playerProfile', fn ($profiles) => $profiles->where('display_name', 'like', "%{$search}%")))
                    ->orWhereHas('club', fn ($clubs) => $clubs->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('tournament', fn ($tournaments) => $tournaments->where('name', 'like', "%{$search}%"));
            });
        })
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

Route::get('tournaments/{tournament:slug}/players', fn (Tournament $tournament) => view('tournaments.players', [
    'tournament' => $tournament->load('club', 'organizer', 'categories'),
]))->name('tournaments.players');

Route::get('tournaments/{tournament:slug}/draws/{category:slug}', function (Tournament $tournament, TournamentCategory $category) {
    abort_unless($category->tournament_id === $tournament->id, 404);

    return view('tournaments.draw', [
        'tournament' => $tournament->load('club', 'organizer', 'categories'),
        'category' => $category,
    ]);
})->scopeBindings()->name('tournaments.draw');

Route::get('tournaments/{tournament:slug}/draws/{category:slug}/{group}', function (Tournament $tournament, TournamentCategory $category, string $group, RoundRobinStandingsService $standings) {
    abort_unless($category->tournament_id === $tournament->id, 404);
    abort_unless($category->draw_mode === 'round_robin', 404);

    $category->load('entrants.players.user.playerProfile', 'matches.players.user.playerProfile');
    $groupName = Str::of($group)->replace('-', ' ')->title()->toString();
    abort_unless($category->entrants->contains('group_name', $groupName), 404);

    return view('tournaments.group', [
        'tournament' => $tournament->load('club', 'organizer', 'categories'),
        'category' => $category,
        'groupName' => $groupName,
        'standings' => $standings->forGroup($category, $groupName),
    ]);
})->scopeBindings()->name('tournaments.draw.group');

Route::get('tournaments/{tournament:slug}/draws/{category:slug}/{group}/matches', function (Tournament $tournament, TournamentCategory $category, string $group, RoundRobinStandingsService $standings) {
    abort_unless($category->tournament_id === $tournament->id, 404);
    abort_unless($category->draw_mode === 'round_robin', 404);

    $groupName = Str::of($group)->replace('-', ' ')->title()->toString();
    $category->load('entrants.players.user.playerProfile', 'matches.players.user.playerProfile');
    abort_unless($category->entrants->contains('group_name', $groupName), 404);

    return view('tournaments.group-matches', [
        'tournament' => $tournament->load('club', 'organizer', 'categories'),
        'category' => $category,
        'groupName' => $groupName,
        'standings' => $standings->forGroup($category, $groupName),
        'matches' => $category->matches
            ->where('draw_group', $groupName)
            ->sortBy(fn (MatchRecord $match) => [$match->scheduled_at?->timestamp ?? 0, $match->draw_position])
            ->values(),
    ]);
})->scopeBindings()->name('tournaments.draw.group.matches');

Route::get('tournaments/{tournament:slug}/matches', function (Tournament $tournament) {
    return view('tournaments.matches', [
        'tournament' => $tournament->load('club', 'organizer', 'categories'),
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
                'group_size' => ['nullable', 'integer', 'in:3,4,5,6'],
                'max_entrants' => ['nullable', 'integer', 'min:2', 'max:256'],
                'status' => ['required', 'in:draft,published,closed'],
            ]);
            $data['group_size'] = $data['draw_mode'] === 'round_robin' ? (int) ($data['group_size'] ?? 4) : 4;
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

        Route::get('{tournament:slug}/draw-engine', fn (Tournament $tournament) => tap(null, function () use ($tournament) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
        }) ?? view('organizer.tournaments.draw-engine', [
            'tournament' => $tournament->load('categories.approvedEntrants.players.user.playerProfile'),
            'drawTypes' => DrawType::cases(),
            'preview' => null,
        ]))->name('draw-engine');

        Route::post('{tournament:slug}/draw-engine/preview', function (Tournament $tournament, DrawGeneratorService $draws, ScheduleGeneratorService $scheduler) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            $data = request()->validate([
                'event_id' => ['required', 'exists:tournament_categories,id'],
                'draw_type' => ['required', 'in:single_elimination,double_elimination,round_robin,pool_to_knockout'],
                'group_size' => ['nullable', 'integer', 'min:3', 'max:8'],
                'qualifiers_per_pool' => ['nullable', 'integer', 'min:1', 'max:4'],
                'courts_count' => ['nullable', 'integer', 'min:1', 'max:50'],
                'court_label_prefix' => ['nullable', 'string', 'max:40'],
                'schedule_start_time' => ['nullable', 'date_format:H:i'],
                'schedule_end_time' => ['nullable', 'date_format:H:i'],
                'match_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
                'rest_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
                'max_matches_per_player_per_day' => ['nullable', 'integer', 'min:1', 'max:12'],
                'days' => ['nullable', 'array'],
                'days.*.date' => ['nullable', 'date'],
                'days.*.start_time' => ['nullable', 'date_format:H:i'],
                'days.*.end_time' => ['nullable', 'date_format:H:i'],
                'days.*.courts_count' => ['nullable', 'integer', 'min:1', 'max:50'],
                'days.*.allowed_stages' => ['nullable', 'array'],
                'days.*.allowed_rounds' => ['nullable', 'array'],
            ]);
            $event = TournamentCategory::where('tournament_id', $tournament->id)->findOrFail($data['event_id']);
            $preview = $scheduler->schedule($tournament, $draws->preview($event, $data['draw_type'], $data), $data);

            return view('organizer.tournaments.draw-engine', [
                'tournament' => $tournament->load('categories.approvedEntrants.players.user.playerProfile'),
                'drawTypes' => DrawType::cases(),
                'preview' => $preview,
                'selectedEvent' => $event,
            ]);
        })->name('draw-engine.preview');

        Route::post('{tournament:slug}/draw-engine/generate', function (Tournament $tournament, DrawGeneratorService $draws, ScheduleGeneratorService $scheduler, DrawMatchPersistenceService $persistence) {
            abort_unless($tournament->organizer_id === auth()->id() || auth()->user()->hasRole('superadmin'), 403);
            $data = request()->validate([
                'event_id' => ['required', 'exists:tournament_categories,id'],
                'draw_type' => ['required', 'in:single_elimination,double_elimination,round_robin,pool_to_knockout'],
                'group_size' => ['nullable', 'integer', 'min:3', 'max:8'],
                'qualifiers_per_pool' => ['nullable', 'integer', 'min:1', 'max:4'],
                'courts_count' => ['nullable', 'integer', 'min:1', 'max:50'],
                'court_label_prefix' => ['nullable', 'string', 'max:40'],
                'schedule_start_time' => ['nullable', 'date_format:H:i'],
                'schedule_end_time' => ['nullable', 'date_format:H:i'],
                'match_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
                'rest_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
                'max_matches_per_player_per_day' => ['nullable', 'integer', 'min:1', 'max:12'],
                'confirm_overwrite' => ['sometimes', 'accepted'],
                'days' => ['nullable', 'array'],
                'days.*.date' => ['nullable', 'date'],
                'days.*.start_time' => ['nullable', 'date_format:H:i'],
                'days.*.end_time' => ['nullable', 'date_format:H:i'],
                'days.*.courts_count' => ['nullable', 'integer', 'min:1', 'max:50'],
                'days.*.allowed_stages' => ['nullable', 'array'],
                'days.*.allowed_rounds' => ['nullable', 'array'],
            ]);
            $event = TournamentCategory::where('tournament_id', $tournament->id)->findOrFail($data['event_id']);

            if ($event->matches()->exists() && ! ($data['confirm_overwrite'] ?? false)) {
                return back()
                    ->withErrors(['confirm_overwrite' => 'Matches already exist for this event. Confirm safe overwrite to regenerate.'])
                    ->withInput();
            }

            $preview = $scheduler->schedule($tournament, $draws->preview($event, $data['draw_type'], $data), $data);
            $created = $persistence->persist($event->load('tournament'), $preview, (bool) ($data['confirm_overwrite'] ?? false));

            return redirect()->route('organizer.tournaments.matches', $tournament)->with('status', "{$created} draw engine matches generated.");
        })->name('draw-engine.generate');

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
    Route::get('/', fn (AdminDashboardService $dashboard) => view('admin.dashboard', $dashboard->data()))->name('dashboard');

    Route::get('users', fn () => view('admin.users', [
        'users' => User::with('playerProfile', 'clubs', 'roles')
            ->withCount('matchPlayers')
            ->when(request('search'), fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when(request('status') === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->when(request('status') === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when(request('role') === 'admin', fn ($query) => $query->role('superadmin'))
            ->when(request('sort') === 'name', fn ($query) => $query->orderBy('name'), fn ($query) => $query->latest())
            ->paginate(20)
            ->withQueryString(),
    ]))->name('users');

    Route::get('users/create', fn () => view('admin.users-form', ['user' => null, 'clubs' => Club::orderBy('name')->get()]))->name('users.create');
    Route::post('users', function (UserAdminService $users) {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'club_id' => ['nullable', 'exists:clubs,id'],
            'smashr_points' => ['nullable', 'integer'],
        ]);
        $users->create($data);

        return redirect()->route('admin.users')->with('status', 'User created.');
    })->name('users.store');
    Route::get('users/{user}', fn (User $user) => view('admin.users-show', [
        'user' => $user->load('playerProfile', 'clubs', 'roles', 'smashrPointAdjustments.admin'),
        'ratingEvents' => RatingEvent::where('user_id', $user->id)->latest()->limit(10)->get(),
    ]))->name('users.show');
    Route::get('users/{user}/edit', fn (User $user) => view('admin.users-form', ['user' => $user->load('playerProfile', 'clubs'), 'clubs' => Club::orderBy('name')->get()]))->name('users.edit');
    Route::patch('users/{user}', function (User $user, UserAdminService $users) {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'phone_number' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'club_id' => ['nullable', 'exists:clubs,id'],
        ]);
        $users->update($user, $data);

        return redirect()->route('admin.users')->with('status', 'User updated.');
    })->name('users.update');
    Route::delete('users/{user}', function (User $user) {
        abort_if($user->id === auth()->id(), 422, 'You cannot delete your own account.');
        $user->delete();

        return redirect()->route('admin.users')->with('status', 'User deleted.');
    })->name('users.destroy');
    Route::patch('users/{user}/superadmin', function (User $user, UserAdminService $users) {
        $users->setSuperadmin($user, request()->boolean('enabled'));

        return back()->with('status', 'User role updated.');
    })->name('users.superadmin');
    Route::patch('users/{user}/suspension', function (User $user, UserAdminService $users) {
        $users->setSuspended($user, request()->boolean('suspended'));

        return back()->with('status', 'User suspension updated.');
    })->name('users.suspension');
    Route::post('users/{user}/points', function (User $user, SmashrPointsService $points) {
        $data = request()->validate([
            'mode' => ['required', 'in:set,add,deduct'],
            'points' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $adjustment = $points->adjust($user, $data['mode'], (int) $data['points'], $data['reason'], auth()->id());

        return back()->with('status', "SMASHR points updated from {$adjustment->before_points} to {$adjustment->after_points}.");
    })->name('users.points');
    Route::post('users/{user}/ratings/regenerate', function (User $user, RatingRecalculationService $recalc) {
        $result = $recalc->apply(RatingAlgorithm::active());

        return back()->with('recalc_preview', $result)->with('status', 'Ratings regenerated.');
    })->name('users.ratings.regenerate');
    Route::post('users/{user}/points/regenerate', function (User $user, SmashrPointsService $points) {
        $adjustment = $points->regenerate($user, auth()->id());

        return back()->with('status', "SMASHR points regenerated from {$adjustment->before_points} to {$adjustment->after_points}.");
    })->name('users.points.regenerate');

    Route::get('clubs', fn () => view('admin.clubs', [
        'clubs' => Club::withCount(['members', 'tournaments'])
            ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString(),
    ]))->name('clubs');
    Route::get('clubs/create', fn () => view('admin.clubs-form', ['club' => null]))->name('clubs.create');
    Route::post('clubs', function (ClubAdminService $clubs) {
        $data = request()->validate(['name' => ['required', 'string', 'max:120'], 'country' => ['nullable', 'string', 'max:80'], 'state' => ['nullable', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:1000']]);
        $clubs->create($data);

        return redirect()->route('admin.clubs')->with('status', 'Club created.');
    })->name('clubs.store');
    Route::get('clubs/{club}', fn (Club $club) => view('admin.clubs-show', ['club' => $club->load('members.playerProfile')->loadCount('members', 'tournaments')]))->name('clubs.show');
    Route::get('clubs/{club}/edit', fn (Club $club) => view('admin.clubs-form', ['club' => $club]))->name('clubs.edit');
    Route::patch('clubs/{club}', function (Club $club, ClubAdminService $clubs) {
        $clubs->update($club, request()->validate(['name' => ['required', 'string', 'max:120'], 'country' => ['nullable', 'string', 'max:80'], 'state' => ['nullable', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:1000']]));

        return redirect()->route('admin.clubs')->with('status', 'Club updated.');
    })->name('clubs.update');
    Route::delete('clubs/{club}', function (Club $club) {
        $club->delete();

        return redirect()->route('admin.clubs')->with('status', 'Club deleted.');
    })->name('clubs.destroy');
    Route::post('clubs/{club}/members', function (Club $club, ClubAdminService $clubs) {
        $data = request()->validate(['email' => ['required', 'email', 'exists:users,email']]);
        $clubs->addMember($club, $data['email']);

        return back()->with('status', 'Club member added.');
    })->name('clubs.members.store');
    Route::delete('clubs/{club}/members/{user}', function (Club $club, User $user) {
        $club->members()->detach($user);

        return back()->with('status', 'Club member removed.');
    })->name('clubs.members.destroy');

    Route::get('tournaments', fn () => view('admin.tournaments', [
        'tournaments' => Tournament::with('club')->withCount('matches')
            ->when(request('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString(),
    ]))->name('tournaments');
    Route::get('tournaments/create', fn () => view('admin.tournaments-form', ['tournament' => null, 'clubs' => Club::orderBy('name')->get()]))->name('tournaments.create');
    Route::post('tournaments', function (TournamentAdminService $tournaments) {
        $data = request()->validate(['club_id' => ['nullable', 'exists:clubs,id'], 'name' => ['required', 'string', 'max:140'], 'country' => ['nullable', 'string', 'max:80'], 'state' => ['nullable', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80'], 'venue' => ['nullable', 'string', 'max:160'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'status' => ['required', 'in:draft,published,archived']]);
        $tournaments->create($data, auth()->id());

        return redirect()->route('admin.tournaments')->with('status', 'Tournament created.');
    })->name('tournaments.store');
    Route::get('tournaments/{tournament}', fn (Tournament $tournament) => view('admin.tournaments-show', ['tournament' => $tournament->load('club', 'categories')->loadCount('matches')]))->name('tournaments.show');
    Route::get('tournaments/{tournament}/edit', fn (Tournament $tournament) => view('admin.tournaments-form', ['tournament' => $tournament, 'clubs' => Club::orderBy('name')->get()]))->name('tournaments.edit');
    Route::patch('tournaments/{tournament}', function (Tournament $tournament, TournamentAdminService $tournaments) {
        $tournaments->update($tournament, request()->validate(['club_id' => ['nullable', 'exists:clubs,id'], 'name' => ['required', 'string', 'max:140'], 'country' => ['nullable', 'string', 'max:80'], 'state' => ['nullable', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80'], 'venue' => ['nullable', 'string', 'max:160'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'status' => ['required', 'in:draft,published,archived']]));

        return redirect()->route('admin.tournaments')->with('status', 'Tournament updated.');
    })->name('tournaments.update');
    Route::patch('tournaments/{tournament}/archive', function (Tournament $tournament, TournamentAdminService $tournaments) {
        $tournaments->archive($tournament);

        return back()->with('status', 'Tournament archived.');
    })->name('tournaments.archive');
    Route::delete('tournaments/{tournament}', function (Tournament $tournament) {
        $tournament->delete();

        return redirect()->route('admin.tournaments')->with('status', 'Tournament deleted.');
    })->name('tournaments.destroy');

    Route::get('matches', fn () => view('admin.matches', [
        'matches' => MatchRecord::with('players.user.playerProfile', 'club', 'tournament', 'tournamentCategory')
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status))
            ->when(request('tournament_id'), fn ($query, $id) => $query->where('tournament_id', $id))
            ->when(request('event_id'), fn ($query, $id) => $query->where('tournament_category_id', $id))
            ->when(request('court'), fn ($query, $court) => $query->where('court_label', 'like', "%{$court}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString(),
        'tournaments' => Tournament::orderBy('name')->get(),
        'events' => TournamentCategory::orderBy('name')->get(),
    ]))->name('matches');
    Route::get('matches/create', fn () => view('admin.matches-create', [
        'clubs' => Club::orderBy('name')->get(),
        'tournaments' => Tournament::orderBy('name')->get(),
        'events' => TournamentCategory::orderBy('name')->get(),
        'users' => User::with('playerProfile')->orderBy('name')->get(),
        'preselectedUserId' => request('user'),
    ]))->name('matches.create');
    Route::post('matches', function (MatchAdminService $matches) {
        $data = request()->validate([
            'format' => ['required', 'in:singles,doubles'],
            'club_id' => ['nullable', 'exists:clubs,id'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'tournament_category_id' => ['nullable', 'exists:tournament_categories,id'],
            'side_a_user_id' => ['required', 'exists:users,id', 'different:side_b_user_id'],
            'side_b_user_id' => ['required', 'exists:users,id'],
            'played_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'court_label' => ['nullable', 'string', 'max:80'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
            'winner_side' => ['required', 'in:A,B'],
            'status' => ['required', 'in:pending_confirmation,disputed,void'],
        ]);
        $match = $matches->create($data, auth()->id());

        return redirect()->route('admin.matches.show', $match)->with('status', 'Match created.');
    })->name('matches.store');
    Route::post('matches/bulk', function (MatchAdminService $matches, RatingService $ratings) {
        $data = request()->validate([
            'match_ids' => ['nullable', 'array'],
            'match_ids.*' => ['integer', 'exists:matches,id'],
            'action' => ['required', 'in:confirm,void'],
            'all_filtered' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:pending_confirmation,confirmed,disputed,void'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'event_id' => ['nullable', 'exists:tournament_categories,id'],
            'court' => ['nullable', 'string', 'max:80'],
        ]);
        $matchIds = request()->boolean('all_filtered')
            ? MatchRecord::query()
                ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($data['tournament_id'] ?? null, fn ($query, $id) => $query->where('tournament_id', $id))
                ->when($data['event_id'] ?? null, fn ($query, $id) => $query->where('tournament_category_id', $id))
                ->when($data['court'] ?? null, fn ($query, $court) => $query->where('court_label', 'like', "%{$court}%"))
                ->pluck('id')
                ->all()
            : ($data['match_ids'] ?? []);

        if (empty($matchIds)) {
            throw ValidationException::withMessages(['match_ids' => 'Select at least one match or choose all filtered results.']);
        }

        $result = $matches->bulk($matchIds, $data['action'], $ratings);
        $message = "{$result['updated']} matches {$data['action']}ed.";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} could not be processed.";
        }

        return back()->with('status', $message);
    })->name('matches.bulk');
    Route::get('matches/{match}', fn (MatchRecord $match) => view('admin.matches-show', ['match' => $match->load('players.user.playerProfile', 'club', 'tournament', 'tournamentCategory')]))->name('matches.show');
    Route::get('matches/{match}/edit', fn (MatchRecord $match) => view('admin.matches-form', ['match' => $match->load('players.user.playerProfile'), 'clubs' => Club::orderBy('name')->get(), 'tournaments' => Tournament::orderBy('name')->get(), 'events' => TournamentCategory::orderBy('name')->get()]))->name('matches.edit');
    Route::patch('matches/{match}/confirm', function (MatchRecord $match, RatingService $ratings) {
        $ratings->confirmAsAdmin($match);

        return back()->with('status', 'Match confirmed.');
    })->name('matches.confirm');
    Route::patch('matches/{match}/dispute', function (MatchRecord $match, MatchAdminService $matches) {
        $matches->markDisputed($match);

        return back()->with('status', 'Match disputed.');
    })->name('matches.dispute');
    Route::patch('matches/{match}/void', function (MatchRecord $match, MatchAdminService $matches) {
        $matches->void($match);

        return back()->with('status', 'Match voided.');
    })->name('matches.void');
    Route::patch('matches/{match}', function (MatchRecord $match, RatingService $ratings, MatchAdminService $matches) {
        $data = request()->validate(['club_id' => ['nullable', 'exists:clubs,id'], 'tournament_id' => ['nullable', 'exists:tournaments,id'], 'tournament_category_id' => ['nullable', 'exists:tournament_categories,id'], 'played_at' => ['nullable', 'date'], 'scheduled_at' => ['nullable', 'date'], 'court_label' => ['nullable', 'string', 'max:80'], 'winner_side' => ['required', 'in:A,B'], 'status' => ['required', 'in:pending_confirmation,confirmed,disputed,void']]);
        $matches->update($match, $data, $ratings);

        return redirect()->route('admin.matches')->with('status', 'Match updated.');
    })->name('matches.update');
    Route::delete('matches/{match}', function (MatchRecord $match) {
        $match->delete();

        return redirect()->route('admin.matches')->with('status', 'Match deleted.');
    })->name('matches.destroy');

    Route::get('algorithms', fn () => view('admin.algorithms', ['algorithms' => RatingAlgorithm::latest()->paginate(20), 'activeAlgorithm' => RatingAlgorithm::active(), 'preview' => session('recalc_preview')]))->name('algorithms');
    Route::get('algorithms/create', fn () => view('admin.algorithms-form', ['algorithm' => null]))->name('algorithms.create');
    Route::post('algorithms', function (AlgorithmAdminService $algorithms) {
        $data = request()->validate(['name' => ['required', 'string', 'max:120'], 'version' => ['required', 'string', 'max:40', 'unique:rating_algorithms,version'], 'settings.starting_rating' => ['required', 'numeric'], 'settings.min_rating' => ['required', 'numeric'], 'settings.max_rating' => ['required', 'numeric'], 'settings.base_delta' => ['required', 'numeric'], 'settings.margin_weight' => ['required', 'numeric'], 'settings.max_margin_bonus' => ['required', 'integer'], 'settings.rating_scale_divisor' => ['required', 'numeric']]);
        $algorithms->create($data, auth()->id());

        return redirect()->route('admin.algorithms')->with('status', 'Algorithm draft created.');
    })->name('algorithms.store');
    Route::get('algorithms/{algorithm}/edit', fn (RatingAlgorithm $algorithm) => view('admin.algorithms-form', ['algorithm' => $algorithm]))->name('algorithms.edit');
    Route::patch('algorithms/{algorithm}', function (RatingAlgorithm $algorithm, AlgorithmAdminService $algorithms) {
        $data = request()->validate(['name' => ['required', 'string', 'max:120'], 'version' => ['required', 'string', 'max:40', 'unique:rating_algorithms,version,'.$algorithm->id], 'settings.starting_rating' => ['required', 'numeric'], 'settings.min_rating' => ['required', 'numeric'], 'settings.max_rating' => ['required', 'numeric'], 'settings.base_delta' => ['required', 'numeric'], 'settings.margin_weight' => ['required', 'numeric'], 'settings.max_margin_bonus' => ['required', 'integer'], 'settings.rating_scale_divisor' => ['required', 'numeric']]);
        $algorithms->update($algorithm, $data);

        return redirect()->route('admin.algorithms')->with('status', 'Algorithm draft updated.');
    })->name('algorithms.update');
    Route::post('algorithms/{algorithm}/duplicate', function (RatingAlgorithm $algorithm, AlgorithmAdminService $algorithms) {
        $copy = $algorithms->duplicate($algorithm, auth()->id());

        return redirect()->route('admin.algorithms.edit', $copy)->with('status', 'Algorithm duplicated.');
    })->name('algorithms.duplicate');
    Route::patch('algorithms/{algorithm}/activate', function (RatingAlgorithm $algorithm, AlgorithmAdminService $algorithms) {
        $algorithms->activate($algorithm);

        return back()->with('status', 'Algorithm activated.');
    })->name('algorithms.activate');
    Route::patch('algorithms/{algorithm}/archive', function (RatingAlgorithm $algorithm, AlgorithmAdminService $algorithms) {
        $algorithms->archive($algorithm);

        return back()->with('status', 'Algorithm archived.');
    })->name('algorithms.archive');
    Route::delete('algorithms/{algorithm}', function (RatingAlgorithm $algorithm, AlgorithmAdminService $algorithms) {
        $algorithms->deleteDraft($algorithm);

        return redirect()->route('admin.algorithms')->with('status', 'Algorithm deleted.');
    })->name('algorithms.destroy');
    Route::post('algorithms/{algorithm}/recalculate/preview', fn (RatingAlgorithm $algorithm, RatingRecalculationService $recalc) => back()->with('recalc_preview', $recalc->preview($algorithm)))->name('algorithms.recalculate.preview');
    Route::post('algorithms/{algorithm}/recalculate/apply', fn (RatingAlgorithm $algorithm, RatingRecalculationService $recalc) => back()->with('recalc_preview', $recalc->apply($algorithm))->with('status', 'Ratings recalculated.'))->name('algorithms.recalculate.apply');
});

require __DIR__.'/auth.php';
