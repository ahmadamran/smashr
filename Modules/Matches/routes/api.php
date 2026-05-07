<?php

use Illuminate\Support\Facades\Route;
use Modules\Matches\Http\Controllers\MatchesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('matches', MatchesController::class)->names('matches');
});
