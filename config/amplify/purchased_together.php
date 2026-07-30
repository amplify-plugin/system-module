<?php

use Amplify\System\Backend\Models\CustomerOrder;

/*
|--------------------------------------------------------------------------
| Frequently Purchased Together — file defaults
|--------------------------------------------------------------------------
|
| Runtime values are loaded from system_configurations (see PurchasedTogetherSettingSeeder).
| These defaults apply before installation or when a DB value is missing.
|
*/

return [
    'enabled' => true,

    'eligible_order_statuses' => [
        'Complete',
        'Submitted',
        'Approved',
        'Processing',
        'Pending',
    ],

    'order_type' => CustomerOrder::IS_ORDER_TYPE,

    'months_lookback' => null,

    'use_order_chunks' => false,

    'order_chunk_size' => 500,

    'insert_chunk_size' => 1000,
];
