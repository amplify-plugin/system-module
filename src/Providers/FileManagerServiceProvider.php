<?php

namespace Amplify\System\Providers;

use Amplify\System\Media\Http\Middleware\FileManagerACL;
use Amplify\System\Media\Services\ACLService\ACLRepository;
use Amplify\System\Media\Services\ConfigService\ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

class FileManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'file-manager');

        // publish config
        $this->publishes([
            __DIR__ .'/../../config/file-manager.php' => config_path('file-manager.php'),
        ], 'fm-config');

        // publish js and css files - vue-file-manager module
        $this->publishes([
            __DIR__
            .'/resources/assets' => public_path('vendor/file-manager'),
        ], 'fm-assets');
    }

    /**
     * Register the application services.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ .'/../../config/file-manager.php',
            'file-manager'
        );

        // Config Repository
        $this->app->bind(
            ConfigRepository::class,
            $this->app['config']['file-manager.configRepository']
        );

        // ACL Repository
        $this->app->bind(
            ACLRepository::class,
            $this->app->make(ConfigRepository::class)->getAclRepository()
        );

        // register ACL middleware
        $this->app['router']->aliasMiddleware('fm-acl', FileManagerACL::class);
    }
}
