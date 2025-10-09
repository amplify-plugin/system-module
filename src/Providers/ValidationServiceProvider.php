<?php

namespace Amplify\System\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Validator::extend('ascii_only', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[\x20-\x7E]*$/', $value);
        }, 'The :attribute may contains invalid characters.');
    }
}
