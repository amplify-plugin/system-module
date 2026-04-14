<?php

namespace Amplify\System\Providers;

use Amplify\System\Commands\AddProductThumbnailCommand;
use Amplify\System\Commands\CreateAllLoginCommand;
use Amplify\System\Commands\DeleteProductsCommand;
use Amplify\System\Commands\EasyAskDatabaseExportCommand;
use Amplify\System\Commands\FetchTracePartsCatalogCommand;
use Amplify\System\Commands\HealthCheckupCommand;
use Amplify\System\Commands\IncrementalCatalogUpdate;
use Amplify\System\Commands\MoveStorageToCloud;
use Amplify\System\Commands\SetupEnvCommand;
use Amplify\System\Commands\SitemapGenerateCommand;
use Amplify\System\Commands\TracepartsImportXmlData;
use Amplify\System\Commands\TransformProduct;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EasyAskDatabaseExportCommand::class,
                CreateAllLoginCommand::class,
                DeleteProductsCommand::class,
                FetchTracePartsCatalogCommand::class,
                HealthCheckupCommand::class,
                IncrementalCatalogUpdate::class,
                MoveStorageToCloud::class,
                SetupEnvCommand::class,
                TracepartsImportXmlData::class,
                TransformProduct::class,
                AddProductThumbnailCommand::class,
                SitemapGenerateCommand::class,
            ]);
        }
    }
}
