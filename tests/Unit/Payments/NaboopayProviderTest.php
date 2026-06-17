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
        'payment_providers.naboopay.api_key'       => 'test-naboopay-key',
        'payment_providers.naboopay.webhook_secret' => 'test-webhook-secret',
        'payment_providers.naboopay.sandbox'        => true,
        'payment_providers.naboopay.success_url'    => 'https://safemove.tech/payment/success',
        'payment_providers.naboopay.cancel_url'     => 'https://safemove.tech/payment/cancel',
        'payment_providers.naboopay.callback_url'   => 'https://safemove.tech/api/v1/webhooks/naboopay',
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

    $response = $this->provider->charge(new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
        metadata: ['booking_id' => 42, 'user_id' => 1, 'customer_phone' => '+221770000000'],
    ));

    expect($response->provider)->toBe(PaymentProviderEnum::NABOOPAY)
        ->and($response->providerTransactionId)->toBe('nabo_tx_123')
        ->and($response->providerStatus)->toBe('pending');
});

it('charge lève RuntimeException si transaction_id absent', function (): void {
    Http::fake(['*/transactions/create' => Http::response(['status' => 'pending'], 200)]);

    expect(fn() => $this->provider->charge(new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
    )))->toThrow(RuntimeException::class, 'missing transaction_id');
});

it('charge lève RuntimeException si payment_url absent', function (): void {
    Http::fake(['*/transactions/create' => Http::response([
        'transaction_id' => 'nabo_tx_123',
        'status' => 'pending',
    ], 200)]);

    expect(fn() => $this->provider->charge(new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
    )))->toThrow(RuntimeException::class, 'missing payment_url');
});

it('charge lève RuntimeException si API retourne 500', function (): void {
    Http::fake(['*/transactions/create' => Http::response(['error' => 'Internal error'], 500)]);

    expect(fn() => $this->provider->charge(new PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'booking-42',
    )))->toThrow(RuntimeException::class);
});

// ─── refund ───────────────────────────────────────────────────────────────

it('refund retourne completed si API success', function (): void {
    Http::fake(['*/transactions/refund' => Http::response(['status' => 'success'], 200)]);

    $response = $this->provider->refund(new RefundRequestData(
        provider: PaymentProviderEnum::NABOOPAY,
        providerTransactionId: 'nabo_tx_123',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-42',
        reason: 'requested_by_customer',
        metadata: [],
    ));

    expect($response->providerStatus)->toBe('completed');
});

it('refund retourne failed si API retourne statut inconnu', function (): void {
    Http::fake(['*/transactions/refund' => Http::response(['status' => 'error'], 200)]);

    $response = $this->provider->refund(new RefundRequestData(
        provider: PaymentProviderEnum::NABOOPAY,
        providerTransactionId: 'nabo_tx_123',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-42',
        reason: 'requested_by_customer',
        metadata: [],
    ));

    // statut inconnu → failed sécurisé (F-019 + conseil Pavel)
    expect($response->providerStatus)->toBe('failed');
});

// ─── verifyWebhook ────────────────────────────────────────────────────────

it('verifyWebhook retourne true si signature HMAC valide', function (): void {
    $rawBody = '{"event":"transaction.success","transaction_id":"nabo_tx_123"}';
    $sig     = hash_hmac('sha256', $rawBody, 'test-webhook-secret');

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
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: [],
        headers: ['x-naboopay-signature' => 'invalid'],
        signature: 'invalid',
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

    $event = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    ));

    // F-019 — eventId unique
    expect($event->eventId)->toBe('naboopay_nabo_tx_123_transaction.success')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->providerTransactionId)->toBe('nabo_tx_123')
        ->and($event->amount)->toBe(5000)
        ->and($event->currency)->toBe(CurrencyEnum::XOF)
        ->and($event->metadata['booking_id'])->toBe(42);
});

it('normalizeWebhook mappe payment.success correctement', function (): void {
    $payload = ['event' => 'payment.success', 'transaction_id' => 'nabo_tx_456', 'amount' => 3000, 'currency' => 'XOF'];

    $event = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    ));

    expect($event->eventType)->toBe('transaction.success')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->eventId)->toBe('naboopay_nabo_tx_456_payment.success');
});

it('normalizeWebhook mappe transaction.failed correctement', function (): void {
    $payload = ['event' => 'transaction.failed', 'transaction_id' => 'nabo_tx_789', 'amount' => 5000, 'currency' => 'XOF'];

    $event = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    ));

    expect($event->eventType)->toBe('transaction.failed')
        ->and($event->providerStatus)->toBe('failed');
});

it('normalizeWebhook mappe refund.completed correctement', function (): void {
    $payload = ['event' => 'refund.completed', 'transaction_id' => 'nabo_tx_123', 'amount' => 5000, 'currency' => 'XOF'];

    $event = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    ));

    expect($event->eventType)->toBe('refund.completed')
        ->and($event->providerStatus)->toBe('completed');
});

// F-019 — pending et success partagent le même transactionId mais ont des eventIds différents
it('génère des eventIds différents pour pending et success (F-019)', function (): void {
    $txId = 'nabo_shared_tx';

    $pendingPayload = ['event' => 'transaction.pending', 'transaction_id' => $txId, 'amount' => 5000, 'currency' => 'XOF'];
    $successPayload = ['event' => 'transaction.success', 'transaction_id' => $txId, 'amount' => 5000, 'currency' => 'XOF'];

    $pending = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: $pendingPayload,
        headers: [],
    ));
    $success = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: $successPayload,
        headers: [],
    ));

    expect($pending->eventId)->not->toBe($success->eventId)
        ->and($pending->eventId)->toBe('naboopay_nabo_shared_tx_transaction.pending')
        ->and($success->eventId)->toBe('naboopay_nabo_shared_tx_transaction.success');
});

// Statut inconnu → payment.unknown, pas d'exception
it('gère un event inconnu sans exception — retourne payment.unknown (conseil Pavel)', function (): void {
    $payload = ['event' => 'some.new.naboopay.event', 'transaction_id' => 'nabo_tx_xyz', 'amount' => 5000, 'currency' => 'XOF'];

    $event = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
        signature: null,
    ));

    expect($event->eventType)->toBe('payment.unknown')
        ->and($event->providerStatus)->toBe('unknown');
});

it('normalizeWebhook lève RuntimeException si transaction_id absent', function (): void {
    expect(fn() => $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: ['event' => 'transaction.success'],
        headers: [],
    )))->toThrow(RuntimeException::class, 'missing transaction_id');
});

it('normalizeWebhook lève RuntimeException si event absent', function (): void {
    expect(fn() => $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::NABOOPAY,
        rawBody: '{}',
        payload: ['transaction_id' => 'nabo_tx_123'],
        headers: [],
    )))->toThrow(RuntimeException::class, 'missing event');
});

// ─── name ─────────────────────────────────────────────────────────────────

it('name retourne naboopay', function (): void {
    expect($this->provider->name())->toBe('naboopay');
});
