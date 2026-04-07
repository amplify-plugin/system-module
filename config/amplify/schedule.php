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
        'backup_run' => 'Backup Run',
        'product_slug' => 'Generate Product Slug from Name',
        'api_log_clean' => 'Clean API Request Log Data',
        'audit_clean' => 'Clean Activity Log Data',
//        'incremental-catalog' => 'Incremental Catalog Update',
        'customer-report' => 'Customer Registration Report',
        'sitemap_generate' => 'Generate Scheduled Sitemap',
    ],
    'commands' => [
        'product_sync' => [
            'command' => 'amplify:product-sync',
            'enabled' => true,
            'priority' => 3,
            'interval' => 'daily',
            'variables' => [
                '--updatesOnly' => 'N',
                '--processUpdates' => 'N',
                '--limit' => null,
            ],
            'time' => [
                'minute' => '0',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'backup_run' => [
            'command' => 'amplify:create-backup',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [],
            'time' => [
                'minute' => '0',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'api_log_clean' => [
            'command' => 'amplify:api-log-clean',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [
                '--days' => 7,
            ],
            'time' => [
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'audit_clean' => [
            'command' => 'amplify:audit-clean',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [
                '--days' => 30,
            ],
            'time' => [
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'product_slug' => [
            'command' => 'amplify:create-product-slug',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'cron',
            'variables' => [],
            'time' => [
                'minute' => '30',
                'hour' => '23',
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
            'command' => 'amplify:customer-registered-report',
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
            'command' => 'amplify:sitemap-generate',
            'enabled' => true,
            'priority' => 2,
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
