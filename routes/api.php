<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api'],
    'prefix' => 'api'
], function () {
    Route::get('health', \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::class);
});

