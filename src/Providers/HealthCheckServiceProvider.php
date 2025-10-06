<?php

namespace Amplify\System\Providers;

use Amplify\System\Checks\CpuLoad\CpuLoadCheck;
use Amplify\System\Checks\SslCertificate\SslCertificateExpiredCheck;
use Amplify\System\Checks\SslCertificate\SslCertificateValidityCheck;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Checks\BackupsCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DatabaseSizeCheck;
use Spatie\Health\Checks\Checks\DatabaseTableSizeCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\PingCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\Url\Url;

class HealthCheckServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Check::macro('easyAskUrl', function () {
            $url = Url::fromString('/EasyAsk/apps/Advisor.jsp')
                ->withHost(config('amplify.sayt.dictionary.host'))
                ->withScheme(config('amplify.sayt.dictionary.protocol', 'http'))
                ->withQueryParameters([
                    'indexed' => '1',
                    'ie' => 'utf-8',
                    'disp' => 'json',
                    'dct' => config('amplify.sayt.dictionary.dictionary')
                ]);

            $port = config('amplify.sayt.dictionary.port');

            if (is_numeric($port)) {
                $url = $url->withPort($port);
            }

            return $this->url((string)$url);
        });

        Health::checks([
            DebugModeCheck::new(),

            UsedDiskSpaceCheck::new()
                /* ->if(app()->isProduction()) */
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            BackupsCheck::new()
                /* ->if(app()->isProduction()) */
                ->onDisk('backups')
                ->locatedAt('*.zip')
                ->youngestBackShouldHaveBeenMadeBefore(now()->subDays(1))
                ->onlyCheckSizeOnFirstAndLast(),

            CacheCheck::new()
                ->driver('file'),

            CpuLoadCheck::new()
                /* ->if(app()->isProduction()) */
                ->if(PHP_OS === "Linux"),

            DatabaseCheck::new()
            /* ->if(app()->isProduction()) */,

            DatabaseCheck::new()
                ->name('PimDatabase')
                ->label('PIM Database')
                ->connectionName('pim_db')
                ->if(config('amplify.pim.pim_db_enabled', false)),

            DatabaseConnectionCountCheck::new()
                /* ->if(app()->isProduction()) */
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),

            DatabaseSizeCheck::new()
                /* ->if(app()->isProduction()) */
                ->if(in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost']))
                ->failWhenSizeAboveGb(errorThresholdGb: 5.0),

            DatabaseTableSizeCheck::new()
                /* ->if(app()->isProduction()) */
                ->name('ApiLogsTableSize')
                ->table('api_logs', maxSizeInMb: 1_000),

            DatabaseTableSizeCheck::new()
                /* ->if(app()->isProduction()) */
                ->name('ActivityLogsTableSize')
                ->table('audits', maxSizeInMb: 2_000),

            DatabaseTableSizeCheck::new()
                /* ->if(app()->isProduction()) */
                ->name('QueueFailedTableSize')
                ->table('failed_jobs', maxSizeInMb: 100),

            EnvironmentCheck::new(),

            PingCheck::new()
                ->name('SearchEngineAvailability')
                ->easyAskUrl()
                ->retryTimes(3)
                ->timeout(10)
                ->method('GET'),

            QueueCheck::new(),

            RedisCheck::new()
                ->if(class_exists('Redis'))
            /* ->if(app()->isProduction()) */,

            ScheduleCheck::new()
                /* ->if(app()->isProduction()) */
                ->heartbeatMaxAgeInMinutes(2),

            SslCertificateValidityCheck::new()
                /* ->if(app()->isProduction()) */
                ->if(Str::contains(config('app.url'), 'https://'))
                ->url(config('app.url')),

            SslCertificateExpiredCheck::new()
                /* ->if(app()->isProduction()) */
                ->if(Str::contains(config('app.url'), 'https://'))
                ->url(config('app.url'))

        ]);
    }
}
