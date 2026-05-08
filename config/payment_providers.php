<?php

declare(strict_types=1);

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\FakePaymentProvider;
use App\Services\Payments\KkiapayProvider;
use App\Services\Payments\StripeProvider;

return [
    'default' => PaymentProviderEnum::FAKE->value,

    'providers' => [
        PaymentProviderEnum::FAKE->value => FakePaymentProvider::class,
        PaymentProviderEnum::KKIAPAY->value => KkiapayProvider::class,
        PaymentProviderEnum::STRIPE->value => StripeProvider::class,
    ],

    'routing' => [
        'SN' => [
            PaymentMethodEnum::MOBILE_MONEY->value => PaymentProviderEnum::KKIAPAY->value,
            PaymentMethodEnum::CARD->value => PaymentProviderEnum::KKIAPAY->value,
        ],

        'MA' => [
            PaymentMethodEnum::CARD->value => PaymentProviderEnum::STRIPE->value,
        ],

        'FR' => [
            PaymentMethodEnum::CARD->value => PaymentProviderEnum::STRIPE->value,
        ],
    ],

    'stripe' => [
        'api_key'        => env('STRIPE_API_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'success_url'    => env('STRIPE_SUCCESS_URL', 'https://example.com/payment/success'),
        'cancel_url'     => env('STRIPE_CANCEL_URL', 'https://example.com/payment/cancel'),
    ],

    'kkiapay' => [
        'public_key'     => env('KKIAPAY_PUBLIC_KEY'),
        'private_key'    => env('KKIAPAY_PRIVATE_KEY'),
        'secret'         => env('KKIAPAY_SECRET'),
        'webhook_secret' => env('KKIAPAY_WEBHOOK_SECRET'),
        'sandbox'        => env('KKIAPAY_SANDBOX', true),
    ],
];
