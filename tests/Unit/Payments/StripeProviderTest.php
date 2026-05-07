<?php

declare(strict_types=1);

use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\StripeProvider;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config([
        'payment_providers.stripe.api_key'        => 'sk_test_fake',
        'payment_providers.stripe.webhook_secret'  => 'whsec_fake',
        'payment_providers.stripe.success_url'     => 'https://example.com/success',
        'payment_providers.stripe.cancel_url'      => 'https://example.com/cancel',
    ]);

    $this->provider = app(StripeProvider::class);
});

// --- normalizeWebhook ---

it('normalise payment_intent.succeeded → transaction.success', function (): void {
    $payload = [
        'id'   => 'evt_stripe_001',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id'              => 'pi_001',
                'status'          => 'succeeded',
                'amount'          => 5000,
                'amount_received' => 5000,
                'currency'        => 'eur',
                'metadata'        => ['booking_id' => '42'],
            ],
        ],
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventId)->toBe('evt_stripe_001')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->providerTransactionId)->toBe('pi_001')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->amount)->toBe(5000)
        ->and($event->currency)->toBe(CurrencyEnum::EUR)
        ->and($event->provider)->toBe(PaymentProviderEnum::STRIPE);
});

it('normalise payment_intent.payment_failed → transaction.failed', function (): void {
    $payload = [
        'id'   => 'evt_stripe_002',
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id'       => 'pi_002',
                'status'   => 'requires_payment_method',
                'amount'   => 3000,
                'currency' => 'eur',
                'metadata' => [],
            ],
        ],
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventType)->toBe('transaction.failed')
        ->and($event->providerStatus)->toBe('failed')
        ->and($event->providerTransactionId)->toBe('pi_002');
});

it('normalise charge.refunded → refund.completed avec payment_intent comme providerTransactionId', function (): void {
    $payload = [
        'id'   => 'evt_stripe_003',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id'              => 'ch_001',
                'payment_intent'  => 'pi_003',
                'amount_refunded' => 2000,
                'currency'        => 'eur',
                'metadata'        => [],
            ],
        ],
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventType)->toBe('refund.completed')
        ->and($event->providerTransactionId)->toBe('pi_003')
        ->and($event->amount)->toBe(2000)
        ->and($event->providerStatus)->toBe('completed');
});

it('lève une exception si le payload est invalide', function (): void {
    $payload = ['type' => ''];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    expect(fn() => $this->provider->normalizeWebhook($verification))
        ->toThrow(RuntimeException::class, 'Stripe webhook payload is invalid.');
});

it('lève une exception si providerTransactionId est vide', function (): void {
    $payload = [
        'id'   => 'evt_stripe_004',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id'       => '',
                'amount'   => 1000,
                'currency' => 'eur',
                'metadata' => [],
            ],
        ],
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    expect(fn() => $this->provider->normalizeWebhook($verification))
        ->toThrow(RuntimeException::class, 'Stripe webhook missing provider transaction id.');
});

// --- verifyWebhook ---

it('retourne false si signature absente', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: null,
    );

    expect($this->provider->verifyWebhook($verification))->toBeFalse();
});

it('retourne false si signature invalide', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::STRIPE,
        rawBody: '{"type":"payment_intent.succeeded"}',
        payload: [],
        headers: ['stripe-signature' => 'invalid_sig'],
        signature: 'invalid_sig',
    );

    expect($this->provider->verifyWebhook($verification))->toBeFalse();
});

// --- name ---

it('retourne le bon nom provider', function (): void {
    expect($this->provider->name())->toBe('stripe');
});
