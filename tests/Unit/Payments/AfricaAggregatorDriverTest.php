<?php

declare(strict_types=1);

use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\AfricaAggregatorDriver;
use App\Services\Payments\NaboopayProvider;
use App\Services\Payments\PayDunyaProvider;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────

function makeDriver(PayDunyaProvider $paydunya, NaboopayProvider $naboopay): AfricaAggregatorDriver
{
    return new AfricaAggregatorDriver($paydunya, $naboopay);
}

function snChargeRequest(): PaymentRequestData
{
    return new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'sn-booking-001',
        metadata: ['booking_id' => 1],
    );
}

// ─── charge() primaire ────────────────────────────────────────────────────

it('charge() utilise PayDunya en provider primaire si disponible', function () {
    config([
        'payment_providers.paydunya.enabled'      => true,
        'payment_providers.paydunya.master_key'   => 'test-key',
        'payment_providers.paydunya.private_key'  => 'test-pk',
        'payment_providers.paydunya.token'        => 'test-token',
        'payment_providers.paydunya.sandbox'      => true,
        'payment_providers.paydunya.success_url'  => 'https://example.com/success',
        'payment_providers.paydunya.cancel_url'   => 'https://example.com/cancel',
        'payment_providers.paydunya.callback_url' => 'https://example.com/webhook',
    ]);

    Http::fake([
        '*/checkout-invoice/create' => Http::response([
            'token'  => 'pd_token_001',
            'status' => 'pending',
        ], 200),
    ]);

    $driver = app(AfricaAggregatorDriver::class);
    $response = $driver->charge(snChargeRequest());

    expect($response->provider)->toBe(PaymentProviderEnum::PAYDUNYA)
        ->and($response->providerTransactionId)->toBe('pd_token_001')
        ->and($response->providerStatus)->toBe('pending')
        ->and($driver->getActiveProvider())->toBe(PaymentProviderEnum::PAYDUNYA->value);
});

it('getActiveProvider() retourne paydunya par défaut', function () {
    $driver = app(AfricaAggregatorDriver::class);
    expect($driver->getActiveProvider())->toBe(PaymentProviderEnum::PAYDUNYA->value);
});

it('getProviders() retourne paydunya et naboopay', function () {
    $driver = app(AfricaAggregatorDriver::class);

    expect($driver->getProviders())->toBe([
        PaymentProviderEnum::PAYDUNYA->value,
        PaymentProviderEnum::NABOOPAY->value,
    ]);
});

it('isAvailable() retourne true si PayDunya est activé', function () {
    config(['payment_providers.paydunya.enabled' => true]);

    expect(app(AfricaAggregatorDriver::class)->isAvailable())->toBeTrue();
});

it('isAvailable() retourne false si PayDunya désactivé ET Naboopay indisponible', function () {
    config([
        'payment_providers.paydunya.enabled' => false,
        'payment_providers.naboopay.enabled' => false,
    ]);

    expect(app(AfricaAggregatorDriver::class)->isAvailable())->toBeFalse();
});

// ─── refund() routing par provider d'origine ──────────────────────────────

it('refund() route vers PayDunya si original_provider = paydunya', function () {
    config([
        'payment_providers.paydunya.enabled'      => true,
        'payment_providers.paydunya.master_key'   => 'test-key',
        'payment_providers.paydunya.private_key'  => 'test-pk',
        'payment_providers.paydunya.token'        => 'test-token',
        'payment_providers.paydunya.sandbox'      => true,
        'payment_providers.paydunya.callback_url' => 'https://example.com/webhook',
    ]);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'pd_token_001',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-001',
        reason: 'customer request',
        metadata: ['original_provider' => 'paydunya'],
    );

    $driver   = app(AfricaAggregatorDriver::class);
    $response = $driver->refund($request);

    // PayDunya retourne pending_manual (refund manuel)
    expect($response->provider)->toBe(PaymentProviderEnum::PAYDUNYA)
        ->and($response->providerStatus)->toBe('pending_manual');
});

it('refund() route vers PayDunya par défaut si original_provider absent', function () {
    config([
        'payment_providers.paydunya.enabled'      => true,
        'payment_providers.paydunya.master_key'   => 'test-key',
        'payment_providers.paydunya.private_key'  => 'test-pk',
        'payment_providers.paydunya.token'        => 'test-token',
        'payment_providers.paydunya.sandbox'      => true,
        'payment_providers.paydunya.callback_url' => 'https://example.com/webhook',
    ]);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'pd_token_002',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-002',
        reason: 'dispute',
        metadata: [], // pas d'original_provider → fallback paydunya
    );

    $response = app(AfricaAggregatorDriver::class)->refund($request);

    expect($response->provider)->toBe(PaymentProviderEnum::PAYDUNYA);
});
