<?php

use Illuminate\Support\Facades\Route;
use Modules\Players\Http\Controllers\PlayersController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('players', PlayersController::class)->names('players');
});
