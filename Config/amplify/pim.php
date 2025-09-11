<?php

return [
    'auto_publish' => false,
    'required_fields' => true,
    'use_classifications' => true,
    'categorization_required' => true,
    'use_product_specific_detail_page' => true,
    'use_minimum_order_quantity' => false,
    'mandatory_fields' => [],

    // @see product/create/tabs/BasicInfo.vue for usage
    'mandatory_field_labels' => [
        'product_name' => 'Product Name',
        'product_code' => 'Product Code',
        'short_description' => 'Short Description',
        'description' => 'Description',
        'categories' => 'Categories',
        'product_classification_id' => 'Product Classification',
    ],

    'pim_db_enabled' => env('DB_PIM_ENABLED', false),
    'document_type' => null,
];
