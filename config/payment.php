<?php

return [
    'fake' => [
        'mode' => env('FAKE_PAYMENT_MODE', 'success'),
        // success | pending | failure
    ],
];
