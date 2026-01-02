<?php

use Amplify\System\Http\Security\Controllers\CaptchaController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('health', \Spatie\Health\Http\Controllers\SimpleHealthCheckController::class);

    Route::controller(CaptchaController::class)->group(function (){
        Route::get('captcha/api/{config?}', 'getCaptchaApi');
        Route::get('captcha/{config?}', 'getCaptcha');
        Route::get('admin/reload-captcha', 'reloadCaptcha');
        Route::get('reload-captcha', 'reloadCaptcha');
    });
});
