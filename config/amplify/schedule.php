<?php

/*
|--------------------------------------------------------------------------
| Amplify schedule config
|--------------------------------------------------------------------------
|
| time_zone => any time that support by php
| time => In which time schedule support 24h format
| interval => List of some schedule interval
| commands => List of commands that run dynamically
| command => enable => Command active or deactivate
| command => variables => Command parameters at least [] required
|
*/

return [
    'logger_enabled' => true,
    'timezone' => 'UTC',
    'catalog_sync_enabled' => env('AMPLIFY_CATALOG_SYNC_ENABLED', false),
    'labels' => [
        'product_sync' => 'Catalog Synchronization',
//        'incremental-catalog' => 'Incremental Catalog Update',
        'customer-report' => 'Customer Registration Report',
        'sitemap_generate' => 'Generate Scheduled Sitemap',
    ],
    'commands' => [
        'product_sync' => [
            'command' => \Amplify\ErpApi\Commands\ProductSyncCommand::class,
            'enabled' => true,
            'priority' => 3,
            'interval' => 'daily',
            'variables' => [
                '--updates-only' => 'N',
                '--process-updates' => 'N',
                '--limit' => null,
                '--auto-update' => 'Y',
            ],
            'time' => [
                'minute' => '0',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        //@TODO will enabled when cal-tool migrated
/*        'incremental-catalog' => [
            'command' => 'amplify:incremental-catalog-update',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [],
            'time' => [
                'minute' => '0',
                'hour' => '0',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'delete_products' => [
            'command' => 'amplify:delete-products',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [],
            'time' => [
                'minute' => '0',
                'hour' => '0',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],*/

        'customer-report' => [
            'command' => \Amplify\System\Backend\Commands\CustomerRegisteredReportCommand::class,
            'enabled' => true,
            'priority' => 2,
            'interval' => 'monthly',
            'variables' => ['--days' => 30],
            'time' => [
                'minute' => '0',
                'hour' => '0',
                'day' => '1',
                'month' => '*',
                'weekday' => '*',
            ],
        ],
        'sitemap_generate' => [
            'command' => \Amplify\System\Commands\SitemapGenerateCommand::class,
            'enabled' => true,
            'priority' => 10,
            'interval' => 'weekly',
            'variables' => [],
            'time' => [
                'minute' => '0',
                'hour' => '0',
                'day' => '1',
                'month' => '*',
                'weekday' => '*',
            ],
        ],
    ],
];
