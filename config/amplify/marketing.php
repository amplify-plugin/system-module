<?php

return [
    'free_ship_threshold' => null,
    'checkout_threshold_replace' => 'Free Shipping',
    'discount_percent_to_flat_min_limit' => 0,
    'free_ship_messages' => [
        [
            'text' => 'This is a testing message __limit__',
            'zero_amount_message' => 'This is a zero limit testing message',
            'image' => '',
        ],
    ],
    'social_media_share' => true,
    'social_media_links' => [
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=__webpage_url__',
        'twitter' => 'http://twitter.com/share?url=__webpage_url__',
        'instagram' => 'https://www.instagram.com/?url=__webpage_url__',
        'googleplus' => 'https://plus.google.com/share?url=__webpage_url__',
    ],
];
