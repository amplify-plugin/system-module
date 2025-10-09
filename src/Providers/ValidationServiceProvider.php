<?php

namespace Amplify\System\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Validator::extend('phone_number', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[0-9+\-\(\)\.\s]+$/', $value);
        }, 'The :attribute may only contain digits and phone symbols (+,-,(,),. & space).');

        /**
         * | Example    | Country       |
         * | ---------- | --------------|
         * | 12345      | 🇺🇸 USA         |
         * | 12345-6789 | 🇺🇸 USA (ZIP+4) |
         * | SW1A 1AA   | 🇬🇧 UK          |
         * | K1A 0B1    | 🇨🇦 Canada      |
         * | 4000       | 🇧🇩 Bangladesh  |
         * | 1010       | 🇦🇹 Austria     |
         * | 75008      | 🇫🇷 France      |
         * | 00150      | 🇮🇹 Italy       |
         * | 110001     | 🇮🇳 India       |
         */

        Validator::extend('postal_code', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(?:[A-Z]{2,3}[\-\s])?[A-Za-z0-9][A-Za-z0-9\s\-]{2,10}[A-Za-z0-9]$/i', $value);
        }, 'The :attribute must be a valid postal code.');
    }
}
