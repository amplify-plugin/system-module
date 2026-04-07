<?php

use Amplify\System\Pipelines\AddToCart;
use Amplify\System\Pipelines\ProductDetail;

return [
    'debug' => env('AMPLIFY_DEBUG', false),
    'client_code' => env('AMPLIFY_CLIENT_CODE', 'ACP'),
    'suppress_exception' => env('AMPLIFY_SUPPRESS_EXCEPTION', true),
    'easyask_sftp_export' => env('AMPLIFY_SFTP_EXPORT', false),
    'add_to_cart_pipeline' => [
        AddToCart\DataPreparation::class,
        AddToCart\OnlyDefaultWarehouse::class,
        AddToCart\SingleWarehouseForCart::class,
        AddToCart\ErpInventory::class,
        AddToCart\MinOrderQuantity::class,
        AddToCart\OnlyStandardPackSize::class,
        AddToCart\AllowBackOrder::class,
    ],
    'product_detail_pipeline' => [
        ProductDetail\SelectColumns::class,
        ProductDetail\SkipArchived::class,
    ]
];
