<?php

use Amplify\System\Http\Api\Controllers\ContactFindController;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::group(['middleware' => ['api'], 'prefix' => 'api'], function () {

    Route::get('health', HealthCheckJsonResultsController::class);

    if (config('amplify.api.contact_detail', false)) {
        Route::get('contacts/{contact_code}', ContactFindController::class)
            ->middleware('auth:api')->name('api.contact-by-code');
    }
});
