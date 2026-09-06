<?php

return [
    'enabled' => env('RECENTLY_VIEWED_ENABLED', true),

    'max_items' => (int) env('RECENTLY_VIEWED_MAX_ITEMS', 20),

    'local_storage_key' => 'amplify-rv',
];
