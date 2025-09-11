<?php

return [
    'default' => env('DEFAULT_PUNCHOUT', 'default'),
    'enabled' => true,
    'labels' => [
        'default' => 'Default',
        'trade-centric' => 'TradeCentric',
    ],
    'configurations' => [
        'default' => [
            'adapter' => \Amplify\System\Backend\Services\PunchOutApi\TradeCentricApiService::class,
            'url' => '',
            'username' => '',
            'password' => '',
            'enabled' => true,
        ],
        'trade-centric' => [
            'adapter' => \Amplify\System\Backend\Services\PunchOutApi\TradeCentricApiService::class,
            'url' => 'https://connect.tradecentric.com',
            'username' => '',
            'password' => '',
            'enabled' => true,
        ],
    ],
];
