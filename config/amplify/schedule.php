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
        'backup_clean' => 'Backup Clean',
        'backup_run' => 'Backup Run',
        'product_slug' => 'Generate Product Slug from Name',
        'api_log_clean' => 'Clean API Request Log Data',
        'permission_sync' => 'Permission Synchronization',
        'audit_clean' => 'Clean Activity Log Data',
        'hello_world' => 'Hello World',
        // 'backup_table' => 'Backup Table',
        'incremental-catalog' => 'Incremental Catalog Update',
        'customer-report' => 'Customer Registration Report',
    ],
    'commands' => [
        'product_sync' => [
            'command' => 'product:sync',
            'enabled' => true,
            'priority' => 3,
            'interval' => 'daily',
            'variables' => [
                '--updateOnly' => 'N',
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

        'backup_clean' => [
            'command' => 'backup:clean',
            'enabled' => true,
            'priority' => 1,
            'interval' => 'daily',
            'variables' => [
                '--disable-notifications' => '--no-arg-val--',
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
            'command' => 'backup:run',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [
                '--only-db' => '--no-arg-val--',
                '--disable-notifications' => '--no-arg-val--',
            ],
            'time' => [
                'minute' => '0',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'hello_world' => [
            'command' => 'hello:world',
            'enabled' => false,
            'priority' => 4,
            'interval' => 'cron',
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
            'command' => 'api-log:clean',
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

        'permission_sync' => [
            'command' => 'permission:sync',
            'enabled' => true,
            'priority' => 4,
            'interval' => 'daily',
            'variables' => [],
            'time' => [
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'audit_clean' => [
            'command' => 'audit:clean',
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
            'command' => 'create:product-slug',
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
        'backup_table' => [
            'command' => 'backup:database',
            'enabled' => env('SFTP_EXPORT', false),
            'priority' => 2,
            'interval' => 'daily',
            'variables' => [
                'tableList' => 'attribute_product_classification,attribute_product,attribute_values,'
                    .'attributes,categories,category_product,customer_group_product,customer_groups,'
                    .'customers,manufacturers,option_product_classification,option_product,'
                    .'options,products,product__images,products,warehouses',
            ],
            'time' => [
                'minute' => '0',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'weekday' => '*',
            ],
        ],

        'incremental-catalog' => [
            'command' => 'app:incremental-catalog-update',
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
            'command' => 'app:delete-products',
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
        'customer-report' => [
            'command' => 'amplify:send-customer-registered-report',
            'enabled' => true,
            'priority' => 2,
            'interval' => 'monthly',
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
