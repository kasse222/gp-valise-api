<?php

return [
    'fake' => [
        'mode' => env('FAKE_PAYMENT_MODE', 'success'),
        // success | pending | failure
    ],

    'webhook' => [
        'secret' => env('PAYMENT_WEBHOOK_SECRET', 'dev_webhook_secret'),
    ],
];
