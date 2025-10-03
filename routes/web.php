<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('health', \Spatie\Health\Http\Controllers\SimpleHealthCheckController::class);
});
