<?php

namespace Amplify\System\Providers;

use Amplify\System\Commands\AddProductSlugCommand;
use Amplify\System\Commands\AddProductThumbnailCommand;
use Amplify\System\Commands\BackupDatabase;
use Amplify\System\Commands\CleanApiLogCommand;
use Amplify\System\Commands\CleanAuditCommand;
use Amplify\System\Commands\CreateAllLoginCommand;
use Amplify\System\Commands\CrudControllerBackpackCommand;
use Amplify\System\Commands\CsdErpTokenRefreshCommand;
use Amplify\System\Commands\CustomerRegisteredReportCommand;
use Amplify\System\Commands\DeleteProductsCommand;
use Amplify\System\Commands\FetchTracePartsCatalogCommand;
use Amplify\System\Commands\HealthCheckupCommand;
use Amplify\System\Commands\IncrementalCatalogUpdate;
use Amplify\System\Commands\MoveStorageToCloud;
use Amplify\System\Commands\RemoveUnusedAddressesCommand;
use Amplify\System\Commands\ScopeMakeCommand;
use Amplify\System\Commands\SetupEnvCommand;
use Amplify\System\Commands\SitemapGenerateCommand;
use Amplify\System\Commands\SyncPermissions;
use Amplify\System\Commands\TracepartsImportXmlData;
use Amplify\System\Commands\TraitMakeCommand;
use Amplify\System\Commands\TransformProduct;
use Amplify\System\Commands\UpgradeIssueFix;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AddProductSlugCommand::class,
                BackupDatabase::class,
                CleanApiLogCommand::class,
                CleanAuditCommand::class,
                CreateAllLoginCommand::class,
                DeleteProductsCommand::class,
                FetchTracePartsCatalogCommand::class,
                HealthCheckupCommand::class,
                IncrementalCatalogUpdate::class,
                MoveStorageToCloud::class,
                RemoveUnusedAddressesCommand::class,
                ScopeMakeCommand::class,
                SetupEnvCommand::class,
                SyncPermissions::class,
                TracepartsImportXmlData::class,
                TraitMakeCommand::class,
                TransformProduct::class,
                UpgradeIssueFix::class,
                CustomerRegisteredReportCommand::class,
                CsdErpTokenRefreshCommand::class,
                AddProductThumbnailCommand::class,
                SitemapGenerateCommand::class,
            ]);

            if (class_exists('Backpack\Generators\Services\BackpackCommand')) {
                $this->commands([
                    CrudControllerBackpackCommand::class,
                ]);
            }
        }

    }
}
