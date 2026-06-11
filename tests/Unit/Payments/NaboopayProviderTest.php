<?php

declare(strict_types=1);

use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\NaboopayProvider;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config([
        'payment_providers.naboopay.api_key'        => 'test-naboopay-key',
        'payment_providers.naboopay.webhook_secret'  => 'test-webhook-secret',
        'payment_providers.naboopay.sandbox'         => true,
        'payment_providers.naboopay.success_url'     => 'https://safemove.tech/payment/success',
        'payment_providers.naboopay.cancel_url'      => 'https://safemove.tech/payment/cancel',
        'payment_providers.naboopay.callback_url'    => 'https://safemove.tech/api/v1/webhooks/naboopay',
    ]);

    $this->provider = app(NaboopayProvider::class);
});

// ─── charge ───────────────────────────────────────────────────────────────

it('charge retourne un PaymentResponseData avec checkout_url', function (): void {
    Http::fake([
        '*/transactions/create' => Http::response([
            'transaction_id' => 'nabo_tx_123',
            'payment_url'    => 'https://pay.naboopay.com/checkout/nabo_tx_123',
            'status'         => 'pending',
        ], 200),
    ]);

    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
        metadata: [
            'booking_id'        => 42,
            'user_id'           => 1,
            'customer_phone'    => '+221770000000',
            'customer_email'    => 'sender@test.com',
            'customer_firstname' => 'Tata',
            'customer_lastname'  => 'Test',
        ],
    );

    $response = $this->provider->charge($request);

    expect($response->provider)->toBe(PaymentProviderEnum::NABOOPAY)
        ->and($response->providerTransactionId)->toBe('nabo_tx_123')
        ->and($response->checkoutUrl)->toBe('https://pay.naboopay.com/checkout/nabo_tx_123')
        ->and($response->providerStatus)->toBe('pending')
        ->and($response->amount)->toBe(5000)
        ->and($response->currency)->toBe(CurrencyEnum::XOF);
});

it('charge lève RuntimeException si transaction_id absent', function (): void {
    Http::fake([
        '*/transactions/create' => Http::response(['status' => 'pending'], 200),
    ]);

    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
    );

    expect(fn() => $this->provider->charge($request))
        ->toThrow(RuntimeException::class, 'missing transaction_id');
});

it('charge lève RuntimeException si payment_url absent', function (): void {
    Http::fake([
        '*/transactions/create' => Http::response([
            'transaction_id' => 'nabo_tx_123',
            'status'         => 'pending',
        ], 200),
    ]);

    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
    );

    expect(fn() => $this->provider->charge($request))
        ->toThrow(RuntimeException::class, 'missing payment_url');
});

it('charge lève RuntimeException si API retourne 500', function (): void {
    Http::fake([
        '*/transactions/create' => Http::response(['error' => 'Internal error'], 500),
    ]);

    $request = new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
    );

    expect(fn() => $this->provider->charge($request))
        ->toThrow(RuntimeException::class);
});

// ─── refund ───────────────────────────────────────────────────────────────

it('refund retourne completed si API success', function (): void {
    Http::fake([
        '*/transactions/refund' => Http::response(['status' => 'success'], 200),
    ]);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::NABOOPAY,
        providerTransactionId: 'nabo_tx_123',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-42',
        reason: 'requested_by_customer',
        metadata: [],
    );

    $response = $this->provider->refund($request);

    expect($response->providerStatus)->toBe('completed')
        ->and($response->providerTransactionId)->toBe('nabo_tx_123');
});

it('refund retourne failed si API retourne statut inconnu', function (): void {
    Http::fake([
        '*/transactions/refund' => Http::response(['status' => 'error'], 200),
    ]);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::NABOOPAY,
        providerTransactionId: 'nabo_tx_123',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-42',
        reason: 'requested_by_customer',
        metadata: [],
    );

    $response = $this->provider->refund($request);

    expect($response->providerStatus)->toBe('failed');
});

// ─── verifyWebhook ────────────────────────────────────────────────────────

it('verifyWebhook retourne true si signature HMAC valide', function (): void {
    $rawBody  = '{"event":"transaction.success","transaction_id":"nabo_tx_123"}';
    $secret   = 'test-webhook-secret';
    $sig      = hash_hmac('sha256', $rawBody, $secret);

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: $rawBody,
        payload: json_decode($rawBody, true),
        headers: ['x-naboopay-signature' => $sig],
        signature: $sig,
    );

    expect($this->provider->verifyWebhook($verification))->toBeTrue();
});

it('verifyWebhook retourne false si signature invalide', function (): void {
    $rawBody = '{"event":"transaction.success","transaction_id":"nabo_tx_123"}';

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: $rawBody,
        payload: json_decode($rawBody, true),
        headers: ['x-naboopay-signature' => 'invalid-signature'],
        signature: 'invalid-signature',
    );

    expect($this->provider->verifyWebhook($verification))->toBeFalse();
});

it('verifyWebhook retourne false si signature absente', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: null,
    );

    expect($this->provider->verifyWebhook($verification))->toBeFalse();
});

// ─── normalizeWebhook ─────────────────────────────────────────────────────

it('normalizeWebhook mappe transaction.success correctement', function (): void {
    $payload = [
        'event'          => 'transaction.success',
        'transaction_id' => 'nabo_tx_123',
        'amount'         => 5000,
        'currency'       => 'XOF',
        'metadata'       => ['booking_id' => 42],
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->provider)->toBe(PaymentProviderEnum::NABOOPAY)
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->providerTransactionId)->toBe('nabo_tx_123')
        ->and($event->amount)->toBe(5000)
        ->and($event->currency)->toBe(CurrencyEnum::XOF)
        ->and($event->metadata['booking_id'])->toBe(42);
});

it('normalizeWebhook mappe payment.success correctement', function (): void {
    $payload = [
        'event'          => 'payment.success',
        'transaction_id' => 'nabo_tx_456',
        'amount'         => 3000,
        'currency'       => 'XOF',
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventType)->toBe('transaction.success')
        ->and($event->providerStatus)->toBe('completed');
});

it('normalizeWebhook mappe transaction.failed correctement', function (): void {
    $payload = [
        'event'          => 'transaction.failed',
        'transaction_id' => 'nabo_tx_789',
        'amount'         => 5000,
        'currency'       => 'XOF',
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventType)->toBe('transaction.failed')
        ->and($event->providerStatus)->toBe('failed');
});

it('normalizeWebhook mappe refund.completed correctement', function (): void {
    $payload = [
        'event'          => 'refund.completed',
        'transaction_id' => 'nabo_tx_123',
        'amount'         => 5000,
        'currency'       => 'XOF',
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventType)->toBe('refund.completed')
        ->and($event->providerStatus)->toBe('completed');
});

it('normalizeWebhook lève RuntimeException si transaction_id absent', function (): void {
    $payload = ['event' => 'transaction.success', 'amount' => 5000];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    );

    expect(fn() => $this->provider->normalizeWebhook($verification))
        ->toThrow(RuntimeException::class, 'missing transaction_id');
});

it('normalizeWebhook lève RuntimeException si event absent', function (): void {
    $payload = ['transaction_id' => 'nabo_tx_123', 'amount' => 5000];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    );

    expect(fn() => $this->provider->normalizeWebhook($verification))
        ->toThrow(RuntimeException::class, 'missing event');
});

// ─── name ─────────────────────────────────────────────────────────────────

it('name retourne naboopay', function (): void {
    expect($this->provider->name())->toBe('naboopay');
});
