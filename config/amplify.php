<?php

use Amplify\System\Pipelines\AddToCart;
use Amplify\System\Pipelines\Checkout;
use Amplify\System\Pipelines\ProductDetail;

return [
    'timezone' => env('AMPLIFY_TIMEZONE', 'UTC'),
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
    ],
    'checkout_pipeline'=> [
// Authentication / authorization
        Checkout\ResolveCustomer::class,
        Checkout\ResolveContact::class,

        // Input validation
        Checkout\ValidateBilling::class,
        Checkout\ValidateShipping::class,
        Checkout\ValidateItems::class,

        // Server-side resolution
        Checkout\ResolveProducts::class,
        Checkout\ResolveCustomerPricing::class,
        Checkout\ValidateInventory::class,

        // Server-side calculations
        Checkout\CalculateSubtotal::class,
        Checkout\CalculateShipping::class,
        Checkout\CalculateTax::class,
        Checkout\CalculateAdditionalCharges::class,
        Checkout\CalculateTotal::class,

        // Payment / final validation
        Checkout\ValidatePayment::class,
        Checkout\ValidateCheckoutVersion::class,
        Checkout\ValidateIdempotency::class,
        Checkout\ValidateCheckoutTotals::class,

        // Persistence should be the final stage.
        Checkout\CreateOrder::class,
    ]
];
