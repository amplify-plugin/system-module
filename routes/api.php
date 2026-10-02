<?php

use Amplify\System\Http\Api\Controllers\ContactFindController;
use Amplify\System\Http\Payment\PaymentGatewayController;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;

Route::group(['middleware' => ['api'], 'prefix' => 'admin/api'], function () {

    Route::get('health', HealthCheckJsonResultsController::class)->name('admin.api.health');

    if (config('amplify.api.contact_detail', false)) {
        Route::get('contacts/{contact_code}', ContactFindController::class)
            ->middleware('auth:api')->name('api.contact-by-code');
    }
});

Route::group(['middleware' => ['api', 'frontend'], 'prefix' => 'api'], function () {
    Route::get('payment/initialize', [PaymentGatewayController::class, 'initialize'])->name('api.payment.initialize');
});
