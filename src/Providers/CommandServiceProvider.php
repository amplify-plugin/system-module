<?php

namespace Amplify\System\Providers;

use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Amplify\System\Commands\AddProductSlugCommand::class,
                \Amplify\System\Commands\BackupDatabase::class,
                \Amplify\System\Commands\CleanApiLogCommand::class,
                \Amplify\System\Commands\CleanAuditCommand::class,
                \Amplify\System\Commands\CreateAllLoginCommand::class,
                \Amplify\System\Commands\DeleteProductsCommand::class,
                \Amplify\System\Commands\FetchTracePartsCatalogCommand::class,
                \Amplify\System\Commands\HealthCheckupCommand::class,
                \Amplify\System\Commands\IncrementalCatalogUpdate::class,
                \Amplify\System\Commands\MoveStorageToCloud::class,
                \Amplify\System\Commands\RemoveUnusedAddressesCommand::class,
                \Amplify\System\Commands\ScopeMakeCommand::class,
                \Amplify\System\Commands\SetupEnvCommand::class,
                \Amplify\System\Commands\SyncPermissions::class,
                \Amplify\System\Commands\TracepartsImportXmlData::class,
                \Amplify\System\Commands\TraitMakeCommand::class,
                \Amplify\System\Commands\TransformProduct::class,
                \Amplify\System\Commands\UpgradeIssueFix::class,
                \Amplify\System\Commands\CustomerRegisteredReportCommand::class,
                \Amplify\System\Commands\WidgetMakeCommand::class,
                \Amplify\System\Commands\CsdErpTokenRefreshCommand::class,
            ]);

            if (class_exists('Backpack\Generators\Services\BackpackCommand')) {
                $this->commands([
                    \Amplify\System\Commands\CrudControllerBackpackCommand::class,
                ]);
            }
        }

    }

}
