<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Models\User;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Services\RatingRecalculationService;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\Models\Tournament;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

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

Route::get('players/{playerProfile:slug}', fn (PlayerProfile $playerProfile) => view('players.show', [
    'player' => $playerProfile->load('user.clubs'),
]))->name('players.show');

Route::get('clubs/{club:slug}', fn (Club $club) => view('clubs.show', [
    'club' => $club->load('members.playerProfile'),
]))->name('clubs.show');

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
