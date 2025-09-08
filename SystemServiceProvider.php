<?php

namespace Amplify\System;

use Amplify\System\Backend\Models\Attribute;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Facades\AssetsFacade;
use Amplify\System\Observers\AttributeObserver;
use Amplify\System\Observers\CategoryObserver;
use Amplify\System\Observers\ProductObserver;
use Amplify\System\Providers\AuthServiceProvider;
use Amplify\System\Providers\BladeServiceProvider;
use Amplify\System\Providers\CommandServiceProvider;
use Amplify\System\Providers\EventServiceProvider;
use Amplify\System\Support\AssetsLoader;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(EventServiceProvider::class);

        $this->app->register(BladeServiceProvider::class);

        $this->app->register(CommandServiceProvider::class);

        $this->app->register(AuthServiceProvider::class);

        $this->app->singleton(AssetsLoader::class, fn () => new AssetsLoader);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        AliasLoader::getInstance()->alias('Asset', AssetsFacade::class);

        $this->loadObservers();

    }

    private function loadObservers()
    {
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        Attribute::observe(AttributeObserver::class);
    }

}
