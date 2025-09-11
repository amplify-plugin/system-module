<?php

return [
    'debug' => env('AMPLIFY_DEBUG', false),
    'client_code' => env('AMPLIFY_CLIENT_CODE', 'ACP'),
    'suppress_exception' => env('AMPLIFY_SUPPRESS_EXCEPTION', true),
    'log_search' => false,
    'log_payment' => false,
    'log_erp_api' => false,
    'log_email' => false,
];
