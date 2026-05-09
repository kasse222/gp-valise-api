<?php

return [
    'fee_percentage' => (float) env('GPVALISE_FEE_PERCENTAGE', 10),
    'payment_fee_percentage' => (float) env('GPVALISE_PAYMENT_FEE_PERCENTAGE', 2),
    'escrow_delay_hours' => env('GPVALISE_ESCROW_DELAY_HOURS', 48),
];
