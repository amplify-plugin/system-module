<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Class InstallCommand
 */
class HealthCheckupCommand extends Command
{
    public $signature = 'app:health-checkup';

    public $description = 'Configure the system for the `fintech/core` module';

    /**
     * @throws Throwable
     */
    public function handle(): int
    {
        $this->setPermissions();

        $this->components->task('Clear cached bootstrap files', function () {
            Artisan::call('optimize:clear --quiet');
        });

        //        $this->components->task("Publish default assets", function () {
        //            Artisan::call('vendor:publish --tag=fintech-auth-assets --quiet --force');
        //        });
        //
        //        $this->components->task("Publish file manager assets", function () {
        //            Artisan::call('vendor:publish --tag=fm-assets --quiet --force');
        //        });

        $this->components->task('Flush permission cache', function () {
            Artisan::call('permission:cache-reset --quiet');
        });

        $this->components->task('Verify scheduler log file', function () {
            if (! file_exists(storage_path('/logs/scheduler.log'))) {
                @file_put_contents(storage_path('/logs/scheduler.log'), '');
            }
        });

        $this->components->task('Verify queue worker log file', function () {
            if (! file_exists(storage_path('/logs/worker.log'))) {
                @file_put_contents(storage_path('/logs/worker.log'), '');
            }
        });

        $this->checkAvailablePackages();

        $this->setPermissions();

        $this->components->task('Broadcast queue restart signal', function () {
            Artisan::call('queue:restart --quiet');
        });

        return self::SUCCESS;
    }

    private function checkAvailablePackages(): void
    {
        foreach (config('fintech.core.packages', []) as $code => $package) {
            $this->components->twoColumnDetail(
                "<fg=bright-white;bg=bright-blue;options=bold> {$package} </> API routes",
                (config("fintech.{$code}.enabled", false) ? '<fg=green;options=bold>ENABLED</>' : '<fg=red;options=bold>DISABLED</>')
            );
        }
    }

    /**
     * @throws Throwable
     */
    private function setPermissions(): void
    {
        if (PHP_OS_FAMILY == 'Linux') {
            $this->components->task('Verify storage directory permission', function () {
                shell_exec('sudo chown -R ubuntu:ubuntu '.storage_path('logs'));
                shell_exec('sudo chmod -R 777 '.storage_path('logs'));
            });
        }
    }
}
