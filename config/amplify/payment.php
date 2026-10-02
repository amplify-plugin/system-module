<?php

return [
    'labels' => [
        'default' => 'Default',
        'cenpos' => 'CenPOS',
        'aptean' => 'Aptean Pay',
    ],
    'default' => env('AMPLIFY_PAYMENT_GATEWAY', 'default'),
    'allow_credit_payments' => true,
    'allow_payments' => true,
    'allow_bulk_invoice_payments' => true,
    'gateways' => [
        'default' => [
            'adapter' => \Amplify\System\Payment\Services\DefaultPayService::class,
            'payment_url' => '',
            'merchant_id' => '',
            'cenpos_encrypted_mid' => '',
            'secret_key' => '',
        ],
        'cenpos' => [
            'adapter' => \Amplify\System\Payment\Services\CentPosPayService::class,
            'payment_url' => 'https://www.cenpos.net/simplewebpay/cards/',
            'ach_payment_url' => 'https://www.cenpos.net/simplewebpay/checks/',
            'merchant_id' => '',
            'cenpos_encrypted_mid' => '',
            'secret_key' => '',
        ],
        'aptean' => [
            'adapter' => \Amplify\System\Payment\Services\ApteanPayService::class,
            'api_key' => '',
            'product_id' => '',
            'tenant_id' => '',
            'account_id' => '',
        ],
    ],
];
