<?php

declare(strict_types=1);

use App\Data\Payments\PaymentRequestData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentOperatorEnum;
use App\Services\Payments\FakePaymentProvider;
use App\Services\Payments\KkiapayProvider;
use App\Services\Payments\PaymentProviderResolver;
use App\Services\Payments\StripeProvider;

uses(Tests\TestCase::class);

it('resolves kkiapay for Senegal mobile money', function () {
    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-sn-mobile-money',
        operator: PaymentOperatorEnum::WAVE,
    );

    expect(app(PaymentProviderResolver::class)->resolve($request))
        ->toBeInstanceOf(KkiapayProvider::class);
});

it('resolves stripe for Morocco card payment', function () {
    $request = new PaymentRequestData(
        country: 'MA',
        currency: CurrencyEnum::MAD,
        method: PaymentMethodEnum::CARD,
        amount: 10000,
        idempotencyKey: 'test-ma-card',
    );

    expect(app(PaymentProviderResolver::class)->resolve($request))
        ->toBeInstanceOf(StripeProvider::class);
});

it('falls back to fake provider when no route matches', function () {
    $request = new PaymentRequestData(
        country: 'XX',
        currency: CurrencyEnum::EUR,
        method: PaymentMethodEnum::CARD,
        amount: 10000,
        idempotencyKey: 'test-unknown',
    );

    expect(app(PaymentProviderResolver::class)->resolve($request))
        ->toBeInstanceOf(FakePaymentProvider::class);
});

it('resolves country code case-insensitively', function () {
    $request = new PaymentRequestData(
        country: 'sn',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-lowercase-country',
        operator: PaymentOperatorEnum::WAVE,
    );

    expect(app(PaymentProviderResolver::class)->resolve($request))
        ->toBeInstanceOf(KkiapayProvider::class);
});

it('throws when routed provider class is missing', function () {
    config()->set('payment_providers.providers.kkiapay', 'App\\Missing\\MissingProvider');

    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-missing-provider',
        operator: PaymentOperatorEnum::WAVE,
    );

    app(PaymentProviderResolver::class)->resolve($request);
})->throws(RuntimeException::class, 'Payment provider [kkiapay] is not configured.');

it('throws when configured provider does not implement contract', function () {
    config()->set('payment_providers.providers.kkiapay', stdClass::class);

    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-invalid-provider',
        operator: PaymentOperatorEnum::WAVE,
    );

    app(PaymentProviderResolver::class)->resolve($request);
})->throws(RuntimeException::class, 'Payment provider [kkiapay] must implement PaymentProvider.');
