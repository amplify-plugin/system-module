<?php

namespace Amplify\System\Providers;

use Amplify\System\Commands\AddProductThumbnailCommand;
use Amplify\System\Commands\CreateAllLoginCommand;
use Amplify\System\Commands\DefragmentTablesCommand;
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
                DefragmentTablesCommand::class
            ]);
        }
    }

    private function registerScheduler()
    {
        if (config('app.env') === 'production' && $this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

                if (config('amplify.easyask_sftp_export', false)) {
                    $schedule->command(EasyAskDatabaseExportCommand::class, [
                        'tableList' => 'attribute_product_classification,attribute_product,attribute_values,'
                            . 'attributes,categories,category_product,customer_group_product,customer_groups,'
                            . 'customers,manufacturers,option_product_classification,option_product,'
                            . 'options,products,product__images,products,warehouses'
                    ])
                        ->timezone(\config('amplify.schedule.timezone', \config('app.timezone', 'UTC')))
                        ->daily()
                        ->withoutOverlapping()
                        ->onOneServer();
                }

                $schedule->command('queue:prune-batches', ['--quiet' => true])
                    ->timezone(\config('amplify.schedule.timezone', \config('app.timezone', 'UTC')))
                    ->daily();

                $schedule->command(DefragmentTablesCommand::class, ['--analyze' => true])
                    ->timezone(\config('amplify.schedule.timezone', \config('app.timezone', 'UTC')))
                    ->dailyAt('03:00');

                $schedule->command(DefragmentTablesCommand::class, ['--optimize' => true])
                    ->timezone(\config('amplify.schedule.timezone', \config('app.timezone', 'UTC')))
                    ->saturdays()->at('05:00')
                    ->withoutOverlapping()
                    ->onOneServer();
            });
        }
    }
}
