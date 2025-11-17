<?php

use Amplify\System\Pipelines\AddToCart;

return [
    'debug' => env('AMPLIFY_DEBUG', false),
    'client_code' => env('AMPLIFY_CLIENT_CODE', 'ACP'),
    'suppress_exception' => env('AMPLIFY_SUPPRESS_EXCEPTION', true),
    'add_to_cart_pipeline' => [
        AddToCart\MinOrderQuantity::class,
        AddToCart\OnlyStandardPackSize::class,
        AddToCart\AllowBackOrder::class,
    ]
];
