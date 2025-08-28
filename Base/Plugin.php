<?php

namespace Amplify\System\Base;

use Illuminate\Support\ServiceProvider;

class Plugin extends ServiceProvider {

    public function widgets(): array
    {
        return [];
    }

    public function navigations()
    {
        return [];
    }

    public function permissions()
    {

    }

    public function schedules()
    {
        return [

        ];
    }

    public function consoles(): array
    {
        return [];
    }

    public function routes($route) : void
    {

    }

    public function register()
    {

    }
    public function boot()
    {

    }
}
