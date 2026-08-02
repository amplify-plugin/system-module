<?php

return [
    'protocol' => env('QUIRI_PROTOCOL', 'http'),
    'host' => env('QUIRI_HOST', ''),
    'port' => env('QUIRI_PORT', null),
    'dictionary' => env('QUIRI_DICTIONARY', ''),
    'logger_enabled' => true,
];
