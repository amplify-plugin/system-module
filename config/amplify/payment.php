<?php

return [
    'labels' => [
        'default' => 'Default',
        'cenpos' => 'CenPOS',
    ],
    'default' => 'default',
    'allow_credit_payments' => true,
    'allow_payments' => true,
    'allow_bulk_invoice_payments' => true,
    'logger_enabled' => true,
    'gateways' => [
        'default' => [
            'adapter' => \Amplify\System\Payment\Services\CentPosPayService::class,
            'payment_url' => '',
            'merchant_id' => '',
            'cenpos_encrypted_mid' => '',
            'secret_key' => '',
        ],
        'cenpos' => [
            'adapter' => \Amplify\System\Payment\Services\CentPosPayService::class,
            'payment_url' => 'https://www.cenpos.net/simplewebpay/cards/',
            'ach_payment_url' => 'https://www.cenpos.net/simplewebpay/checks/',
            'merchant_id' => '400002917',
            'cenpos_encrypted_mid' => 'UyaLhXyggZhyyxbnAzVYKg==',
            'secret_key' => '799b27ae1463be856e1c54aa760b2c21ef3fe276d41067c38ce9cd7b8476aec3',
        ],
    ],
];
