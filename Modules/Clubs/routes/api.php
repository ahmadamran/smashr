<?php

use Illuminate\Support\Facades\Route;
use Modules\Clubs\Http\Controllers\ClubsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('clubs', ClubsController::class)->names('clubs');
});
