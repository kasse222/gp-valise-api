<?php

declare(strict_types=1);

use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\FakePaymentProvider;

it('normalise un webhook refund completed', function () {
    $provider = new FakePaymentProvider();

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::FAKE,
        rawBody: json_encode([
            'event' => 'refund.completed',
            'amount' => 10000,
            'currency' => 'EUR',
            'transaction_id' => 'txn_123',
            'event_id' => 'evt_123',
        ]),
        payload: [
            'event' => 'refund.completed',
            'status' => 'completed',
            'amount' => 10000,
            'currency' => 'EUR',
            'provider_transaction_id' => 'txn_123',
            'event_id' => 'evt_123',
        ],
        headers: [],
        signature: null,
        eventId: null,
        correlationId: 'corr_123',
    );

    $event = $provider->normalizeWebhook($verification);

    expect($event->provider)->toBe(PaymentProviderEnum::FAKE);
    expect($event->eventId)->toBe('evt_123');
    expect($event->providerStatus)->toBe('completed');
    expect($event->amount)->toBe(10000);
    expect($event->currency)->toBe(CurrencyEnum::EUR);
});

it('fallback eventId si absent', function () {
    $provider = new FakePaymentProvider();

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::FAKE,
        rawBody: '{}',
        payload: [
            'event' => 'refund.completed',
            'amount' => 10000,
            'currency' => 'EUR',
            'transaction_id' => 'txn_123',
        ],
        headers: [],
        correlationId: 'corr_123',
    );

    $event = $provider->normalizeWebhook($verification);

    expect($event->eventId)->not->toBeNull();
});

it('lève une exception si utilisé en production', function (): void {
    app()->detectEnvironment(fn() => 'production');

    expect(fn() => app(FakePaymentProvider::class)->charge(
        new PaymentRequestData(
            country: 'FR',
            currency: CurrencyEnum::EUR,
            method: PaymentMethodEnum::CARD,
            amount: 1000,
            idempotencyKey: 'key_123',
        )
    ))->toThrow(RuntimeException::class, 'FakePaymentProvider is not allowed in production.');

    app()->detectEnvironment(fn() => 'testing'); // reset
});
