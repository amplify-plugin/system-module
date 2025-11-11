<?php

return [
    'debug' => env('AMPLIFY_DEBUG', false),
    'client_code' => env('AMPLIFY_CLIENT_CODE', 'ACP'),
    'suppress_exception' => env('AMPLIFY_SUPPRESS_EXCEPTION', true),
    'pipelines' => [
        'before_add_to_cart' => [
            \Amplify\System\Pipelines\AddToCart\ErpIsEnabled::class,
            \Amplify\System\Pipelines\AddToCart\ErpInventory::class,
            \Amplify\System\Pipelines\AddToCart\MinOrderQuantity::class,
            \Amplify\System\Pipelines\AddToCart\AllowBackOrder::class,
        ]
    ]
];
