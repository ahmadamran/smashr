<?php

use Illuminate\Support\Facades\Route;
use Modules\Ratings\Http\Controllers\RatingsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('ratings', RatingsController::class)->names('ratings');
});
