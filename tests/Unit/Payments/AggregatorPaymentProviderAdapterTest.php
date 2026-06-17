<?php

declare(strict_types=1);

use App\Contracts\Payments\AggregatorDriver;
use App\Contracts\Payments\PaymentProvider;
use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\AggregatorPaymentProviderAdapter;

uses(Tests\TestCase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────

function makeAdapter(AggregatorDriver $aggregator, PaymentProviderResolverContract $resolver): AggregatorPaymentProviderAdapter
{
    return new AggregatorPaymentProviderAdapter($aggregator, $resolver);
}

function makeChargeRequest(): PaymentRequestData
{
    return new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 10000,
        idempotencyKey: 'test-sn-001',
        metadata: ['booking_id' => 1],
    );
}

function makeChargeResponse(): PaymentResponseData
{
    return new PaymentResponseData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'pd_token_001',
        providerStatus: 'pending',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        checkoutUrl: 'https://paydunya.com/checkout/pd_token_001',
        eventId: null,
        rawPayload: [],
    );
}

// ─── charge() ─────────────────────────────────────────────────────────────

it('charge() délègue à AfricaAggregatorDriver', function () {
    $aggregator = Mockery::mock(AggregatorDriver::class);
    $resolver   = Mockery::mock(PaymentProviderResolverContract::class);
    $request    = makeChargeRequest();
    $expected   = makeChargeResponse();

    $aggregator->shouldReceive('charge')->once()->with($request)->andReturn($expected);

    $response = makeAdapter($aggregator, $resolver)->charge($request);

    expect($response)->toBe($expected);
});

// ─── refund() ─────────────────────────────────────────────────────────────

it('refund() délègue à AfricaAggregatorDriver', function () {
    $aggregator = Mockery::mock(AggregatorDriver::class);
    $resolver   = Mockery::mock(PaymentProviderResolverContract::class);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'pd_token_001',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-001',
        reason: 'customer request',
        metadata: ['original_provider' => 'paydunya'],
    );

    $expected = new PaymentResponseData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'pd_token_001',
        providerStatus: 'pending_manual',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        checkoutUrl: null,
        eventId: null,
        rawPayload: [],
    );

    $aggregator->shouldReceive('refund')->once()->with($request)->andReturn($expected);

    $response = makeAdapter($aggregator, $resolver)->refund($request);

    expect($response)->toBe($expected);
});

// ─── verifyWebhook() ──────────────────────────────────────────────────────

it('verifyWebhook() délègue au provider actif de l\'agrégateur', function () {
    $aggregator       = Mockery::mock(AggregatorDriver::class);
    $resolver         = Mockery::mock(PaymentProviderResolverContract::class);
    $activeProvider   = Mockery::mock(PaymentProvider::class);

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::PAYDUNYA,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: 'valid_sig',
    );

    $aggregator->shouldReceive('getActiveProvider')->once()->andReturn('paydunya');
    $resolver->shouldReceive('resolveByKey')->once()->with('paydunya')->andReturn($activeProvider);
    $activeProvider->shouldReceive('verifyWebhook')->once()->with($verification)->andReturn(true);

    $result = makeAdapter($aggregator, $resolver)->verifyWebhook($verification);

    expect($result)->toBeTrue();
});

it('verifyWebhook() retourne false si provider actif rejette la signature', function () {
    $aggregator     = Mockery::mock(AggregatorDriver::class);
    $resolver       = Mockery::mock(PaymentProviderResolverContract::class);
    $activeProvider = Mockery::mock(PaymentProvider::class);

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::PAYDUNYA,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: 'bad_sig',
    );

    $aggregator->shouldReceive('getActiveProvider')->once()->andReturn('paydunya');
    $resolver->shouldReceive('resolveByKey')->once()->with('paydunya')->andReturn($activeProvider);
    $activeProvider->shouldReceive('verifyWebhook')->once()->andReturn(false);

    expect(makeAdapter($aggregator, $resolver)->verifyWebhook($verification))->toBeFalse();
});

// ─── normalizeWebhook() ───────────────────────────────────────────────────

it('normalizeWebhook() délègue au provider actif de l\'agrégateur', function () {
    $aggregator     = Mockery::mock(AggregatorDriver::class);
    $resolver       = Mockery::mock(PaymentProviderResolverContract::class);
    $activeProvider = Mockery::mock(PaymentProvider::class);

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::PAYDUNYA,
        rawBody: '{}',
        payload: [],
        headers: [],
    );

    $expectedEvent = new PaymentEventData(
        provider: PaymentProviderEnum::PAYDUNYA,
        eventId: 'paydunya_token_001_completed',
        eventType: 'transaction.success',
        providerTransactionId: 'token_001',
        providerStatus: 'completed',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        metadata: [],
        rawPayload: [],
    );

    $aggregator->shouldReceive('getActiveProvider')->once()->andReturn('paydunya');
    $resolver->shouldReceive('resolveByKey')->once()->with('paydunya')->andReturn($activeProvider);
    $activeProvider->shouldReceive('normalizeWebhook')->once()->with($verification)->andReturn($expectedEvent);

    $event = makeAdapter($aggregator, $resolver)->normalizeWebhook($verification);

    expect($event)->toBe($expectedEvent);
});

// ─── name() ───────────────────────────────────────────────────────────────

it('name() retourne africa_aggregator', function () {
    $aggregator = Mockery::mock(AggregatorDriver::class);
    $resolver   = Mockery::mock(PaymentProviderResolverContract::class);

    expect(makeAdapter($aggregator, $resolver)->name())->toBe('africa_aggregator');
});

// ─── Failover naboopay ────────────────────────────────────────────────────

it('normalizeWebhook() utilise naboopay si c\'est le provider actif (failover)', function () {
    $aggregator     = Mockery::mock(AggregatorDriver::class);
    $resolver       = Mockery::mock(PaymentProviderResolverContract::class);
    $naboopayMock   = Mockery::mock(PaymentProvider::class);

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: [],
        headers: [],
    );

    $expectedEvent = new PaymentEventData(
        provider: PaymentProviderEnum::NABOOPAY,
        eventId: 'naboopay_tx_001_transaction.success',
        eventType: 'transaction.success',
        providerTransactionId: 'tx_001',
        providerStatus: 'completed',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        metadata: [],
        rawPayload: [],
    );

    // Agrégateur a basculé sur naboopay
    $aggregator->shouldReceive('getActiveProvider')->twice()->andReturn('naboopay');
    $resolver->shouldReceive('resolveByKey')->twice()->with('naboopay')->andReturn($naboopayMock);
    $naboopayMock->shouldReceive('verifyWebhook')->once()->andReturn(true);
    $naboopayMock->shouldReceive('normalizeWebhook')->once()->andReturn($expectedEvent);

    $adapter = makeAdapter($aggregator, $resolver);

    expect($adapter->verifyWebhook($verification))->toBeTrue();
    expect($adapter->normalizeWebhook($verification))->toBe($expectedEvent);
});
