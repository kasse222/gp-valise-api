<?php

declare(strict_types=1);

use App\Data\Payments\PaymentRequestData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentOperatorEnum;
use App\Services\Payments\AggregatorPaymentProviderAdapter;
use App\Services\Payments\FakePaymentProvider;
use App\Services\Payments\PayDunyaProvider;
use App\Services\Payments\PaymentProviderResolver;
use App\Services\Payments\StripeProvider;

uses(Tests\TestCase::class);

// ─── Routing corridors ────────────────────────────────────────────────────

// F-020 — SN retourne maintenant AggregatorPaymentProviderAdapter (AfricaAggregatorDriver)
it('resolves africa_aggregator for Senegal mobile money (F-020)', function () {
    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-sn-mobile-money',
        operator: PaymentOperatorEnum::WAVE,
    );

    expect(app(PaymentProviderResolver::class)->resolve($request))
        ->toBeInstanceOf(AggregatorPaymentProviderAdapter::class);
});

// F-020 — case-insensitive country → aussi agrégateur
it('resolves africa_aggregator case-insensitively for sn (F-020)', function () {
    $request = new PaymentRequestData(
        country: 'sn',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-lowercase-country',
        operator: PaymentOperatorEnum::WAVE,
    );

    expect(app(PaymentProviderResolver::class)->resolve($request))
        ->toBeInstanceOf(AggregatorPaymentProviderAdapter::class);
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

// ─── resolveByKey direct ──────────────────────────────────────────────────

// Les webhooks Africa arrivent directement via leur provider (pas l'agrégateur)
it('resolveByKey paydunya retourne PayDunyaProvider — utilisé pour les webhooks Africa', function () {
    expect(app(PaymentProviderResolver::class)->resolveByKey('paydunya'))
        ->toBeInstanceOf(PayDunyaProvider::class);
});

it('resolveByKey africa_aggregator retourne AggregatorPaymentProviderAdapter', function () {
    expect(app(PaymentProviderResolver::class)->resolveByKey('africa_aggregator'))
        ->toBeInstanceOf(AggregatorPaymentProviderAdapter::class);
});

// ─── Erreurs de configuration ─────────────────────────────────────────────

it('throws when routed provider class is missing', function () {
    // Pointer stripe vers une classe inexistante (corridor MA n'est pas l'agrégateur)
    config()->set('payment_providers.providers.stripe', 'App\\Missing\\MissingProvider');

    $request = new PaymentRequestData(
        country: 'MA',
        currency: CurrencyEnum::MAD,
        method: PaymentMethodEnum::CARD,
        amount: 10000,
        idempotencyKey: 'test-missing-provider',
    );

    app(PaymentProviderResolver::class)->resolve($request);
})->throws(RuntimeException::class, 'Payment provider [stripe] is not configured.');

it('throws when configured provider does not implement contract', function () {
    config()->set('payment_providers.providers.stripe', stdClass::class);

    $request = new PaymentRequestData(
        country: 'MA',
        currency: CurrencyEnum::MAD,
        method: PaymentMethodEnum::CARD,
        amount: 10000,
        idempotencyKey: 'test-invalid-provider',
    );

    app(PaymentProviderResolver::class)->resolve($request);
})->throws(RuntimeException::class, 'Payment provider [stripe] must implement PaymentProvider.');
