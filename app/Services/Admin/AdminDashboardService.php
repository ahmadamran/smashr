<?php

namespace App\Services\Admin;

use App\Models\User;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Tournaments\Models\Tournament;

class AdminDashboardService
{
    public function data(): array
    {
        return [
            'usersCount' => User::count(),
            'activeUsersCount' => User::whereNull('suspended_at')->count(),
            'suspendedUsersCount' => User::whereNotNull('suspended_at')->count(),
            'clubsCount' => Club::count(),
            'tournamentsCount' => Tournament::count(),
            'publishedTournamentsCount' => Tournament::where('status', 'published')->count(),
            'draftTournamentsCount' => Tournament::where('status', 'draft')->count(),
            'pendingMatchesCount' => MatchRecord::where('status', 'pending_confirmation')->count(),
            'disputedMatchesCount' => MatchRecord::where('status', 'disputed')->count(),
            'activeAlgorithm' => RatingAlgorithm::active(),
            'matchesThisMonth' => MatchRecord::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'newUsersThisMonth' => User::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'recentUsers' => User::latest()->limit(5)->get(),
            'recentTournaments' => Tournament::with('club')->latest()->limit(5)->get(),
            'recentDisputes' => MatchRecord::with('tournament', 'tournamentCategory')->where('status', 'disputed')->latest()->limit(5)->get(),
        ];
    }
}
