<?php

declare(strict_types=1);

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\FakePaymentProvider;
use App\Services\Payments\KkiapayProvider;
use App\Services\Payments\NaboopayProvider;
use App\Services\Payments\PayDunyaProvider;
use App\Services\Payments\StripeProvider;

return [
    'default' => PaymentProviderEnum::FAKE->value,

    'providers' => [
        PaymentProviderEnum::FAKE->value     => FakePaymentProvider::class,
        PaymentProviderEnum::KKIAPAY->value  => KkiapayProvider::class,
        PaymentProviderEnum::PAYDUNYA->value => PayDunyaProvider::class,
        PaymentProviderEnum::STRIPE->value   => StripeProvider::class,
        PaymentProviderEnum::NABOOPAY->value => NaboopayProvider::class,
    ],

    // Routing primaire — le fallback multi-PSP est géré par AfricaAggregatorDriver
    'routing' => [
        'SN' => [
            PaymentMethodEnum::MOBILE_MONEY->value => PaymentProviderEnum::PAYDUNYA->value,
            PaymentMethodEnum::CARD->value         => PaymentProviderEnum::PAYDUNYA->value,
        ],
        'BJ' => [
            PaymentMethodEnum::MOBILE_MONEY->value => PaymentProviderEnum::KKIAPAY->value,
            PaymentMethodEnum::CARD->value         => PaymentProviderEnum::KKIAPAY->value,
        ],
        'CI' => [
            PaymentMethodEnum::MOBILE_MONEY->value => PaymentProviderEnum::KKIAPAY->value,
            PaymentMethodEnum::CARD->value         => PaymentProviderEnum::KKIAPAY->value,
        ],
        'FR' => [
            PaymentMethodEnum::CARD->value => PaymentProviderEnum::STRIPE->value,
        ],
        'MA' => [
            PaymentMethodEnum::CARD->value => PaymentProviderEnum::STRIPE->value,
        ],
    ],

    // Routing agrégateur Africa — fallback automatique si provider primaire down
    'africa_aggregator' => [
        'primary'  => PaymentProviderEnum::PAYDUNYA->value,
        'fallback' => PaymentProviderEnum::NABOOPAY->value,
        'countries' => ['SN', 'CI', 'BJ', 'TG', 'GW', 'ML', 'BF'],
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

    'paydunya' => [
        'enabled'      => env('PAYDUNYA_ENABLED', true),
        'env'          => env('PAYDUNYA_ENV', 'sandbox'),
        'master_key'   => env('PAYDUNYA_MASTER_KEY'),
        'private_key'  => env('PAYDUNYA_PRIVATE_KEY'),
        'token'        => env('PAYDUNYA_TOKEN'),
        'sandbox'      => env('PAYDUNYA_SANDBOX', true),
        'success_url'  => env('PAYDUNYA_SUCCESS_URL'),
        'cancel_url'   => env('PAYDUNYA_CANCEL_URL'),
        'callback_url' => env('PAYDUNYA_CALLBACK_URL'),
    ],

    'naboopay' => [
        'enabled'      => env('NABOOPAY_ENABLED', false),
        'api_key'        => env('NABOOPAY_API_KEY'),
        'webhook_secret' => env('NABOOPAY_WEBHOOK_SECRET'),
        'sandbox'        => env('NABOOPAY_SANDBOX', true),
        'success_url'    => env('NABOOPAY_SUCCESS_URL'),
        'cancel_url'     => env('NABOOPAY_CANCEL_URL'),
        'callback_url'   => env('NABOOPAY_CALLBACK_URL'),
    ],
];
