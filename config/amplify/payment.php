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
            'merchant_id' => env('CENPOS_MERCHANT_ID', ''),
            'cenpos_encrypted_mid' => env('CENPOS_ENCRYPT_KEY', ''),
            'secret_key' => env('CENPOS_SECRET_KEY', ''),
            'css_url' => env('CENPOS_CSS_URL', ''),
        ],
        'aptean' => [
            'adapter' => \Amplify\System\Payment\Services\ApteanPayService::class,
            'api_key' => env('APTEAN_API_KEY',''),
            'product_id' => env('APTEAN_PRODUCT_ID',''),
            'tenant_id' => env('APTEAN_TENANT_ID',''),
            'account_id' => env('APTEAN_ACCOUNT_ID',''),
        ],
    ],
];
