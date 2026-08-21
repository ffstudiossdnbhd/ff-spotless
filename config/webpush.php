<?php

return [
    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@ffspotless.local'),
    ],
    'automatic_padding' => 0,
    'timeout' => 15,
];
