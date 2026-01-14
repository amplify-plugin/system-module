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
use Amplify\System\Providers\HealthCheckServiceProvider;
use Amplify\System\Providers\ValidationServiceProvider;
use Amplify\System\Providers\FileManagerServiceProvider;
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
        $this->mergeConfigFrom(__DIR__ . '/../config/amplify.php', 'amplify');

        foreach (glob(__DIR__ . '/../config/amplify/*.php') as $file) {
            $this->mergeConfigFrom($file, "amplify.".basename($file, '.php'));
        }

        $this->app->register(AuthServiceProvider::class);

        $this->app->register(EventServiceProvider::class);

        $this->app->register(BladeServiceProvider::class);

        $this->app->register(CommandServiceProvider::class);

        $this->app->register(HealthCheckServiceProvider::class);

        $this->app->register(ValidationServiceProvider::class);

        $this->app->register(FileManagerServiceProvider::class);

        $this->app->singleton(AssetsLoader::class, fn() => new AssetsLoader);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'system');

        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadRoutesFrom(__DIR__ . '/../routes/backend.php');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/amplify/system'),
        ], 'system-view');

        $this->publishes([
            __DIR__ . '/../config/amplify.php' => config_path('amplify.php'),
        ], 'amplify-config');

        foreach (glob(__DIR__ . '/../config/amplify/*.php') as $file) {
            $this->publishes([
                $file => config_path('amplify/'. basename($file)),
            ], "amplify-". basename($file, '.php')."-config");
        }

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
