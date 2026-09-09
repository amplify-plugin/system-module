<?php

use Amplify\System\Backend\Models\CustomerOrder;

/*
|--------------------------------------------------------------------------
| Selling Products Dashboard Widget — file defaults
|--------------------------------------------------------------------------
|
| Runtime values are loaded from system_configurations (see SellingProductsSettingSeeder).
| These defaults apply before installation or when a DB value is missing.
|
*/

return [
    'enabled' => true,

    'products_limit' => 10,

    'date_range' => 'last_60_days',

    'custom_start_date' => null,

    'custom_end_date' => null,

    'rank_by' => 'revenue',

    'eligible_order_statuses' => [
        'Complete',
        'Submitted',
        'Approved',
        'Processing',
        'Pending',
    ],

    'order_type' => CustomerOrder::IS_ORDER_TYPE,
];
