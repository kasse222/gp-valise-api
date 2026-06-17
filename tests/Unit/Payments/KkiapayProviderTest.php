<?php

declare(strict_types=1);

use App\Contracts\Payments\KkiapayAdminClientContract;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use App\Payments\Mappers\KkiapayStatusMapper;
use App\Services\Payments\KkiapayProvider;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config([
        'payment_providers.kkiapay.public_key'    => 'test_public_key',
        'payment_providers.kkiapay.private_key'   => 'test_private_key',
        'payment_providers.kkiapay.secret'        => 'test_secret',
        'payment_providers.kkiapay.webhook_secret' => 'test_webhook_secret',
        'payment_providers.kkiapay.sandbox'       => true,
    ]);

    $this->adminClient = Mockery::mock(KkiapayAdminClientContract::class);
    $this->provider    = new KkiapayProvider($this->adminClient, new KkiapayStatusMapper());
});

// ─── refund ───────────────────────────────────────────────────────────────

it('retourne un PaymentResponseData completed si refund réussit', function (): void {
    $this->adminClient->shouldReceive('refund')->once()->with('kkp_tx_001')
        ->andReturn(['status' => 'success', 'transactionId' => 'kkp_tx_001']);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::KKIAPAY,
        providerTransactionId: 'kkp_tx_001',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'idem_001',
        reason: 'customer request',
    );

    $response = $this->provider->refund($request);

    expect($response->providerStatus)->toBe('completed')
        ->and($response->provider)->toBe(PaymentProviderEnum::KKIAPAY);
});

it('lève une RuntimeException si adminClient retourne false', function (): void {
    $this->adminClient->shouldReceive('refund')->once()->with('kkp_tx_002')->andReturn(false);

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::KKIAPAY,
        providerTransactionId: 'kkp_tx_002',
        amount: 3000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'idem_002',
        reason: 'dispute',
    );

    expect(fn() => $this->provider->refund($request))
        ->toThrow(RuntimeException::class, 'Kkiapay refund failed for transaction: kkp_tx_002');
});

it('lève une RuntimeException si adminClient lève une exception', function (): void {
    $this->adminClient->shouldReceive('refund')->once()
        ->andThrow(new \Exception('Network error'));

    $request = new RefundRequestData(
        provider: PaymentProviderEnum::KKIAPAY,
        providerTransactionId: 'kkp_tx_003',
        amount: 1000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'idem_003',
        reason: 'error',
    );

    expect(fn() => $this->provider->refund($request))
        ->toThrow(RuntimeException::class, 'Kkiapay refund failed: Network error');
});

// ─── charge ───────────────────────────────────────────────────────────────

it('retourne un PaymentResponseData pending si charge réussit', function (): void {
    Http::fake([
        'sandbox-api.kkiapay.me/*' => Http::response([
            'transactionId' => 'kkp_tx_004',
            'status'        => 'pending',
            'paymentUrl'    => 'https://sandbox.kkiapay.me/pay/kkp_tx_004',
        ], 200),
    ]);

    $request = new \App\Data\Payments\PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: \App\Enums\PaymentMethodEnum::MOBILE_MONEY,
        amount: 5000,
        idempotencyKey: 'idem_004',
        operator: \App\Enums\PaymentOperatorEnum::MTN,
        metadata: [
            'customer_phone' => '22961000000',
            'customer_firstname' => 'John',
            'customer_lastname' => 'Doe',
            'customer_email' => 'john@example.com',
            'callback_url' => 'https://example.com/webhook',
        ],
    );

    $response = $this->provider->charge($request);

    expect($response->providerTransactionId)->toBe('kkp_tx_004')
        ->and($response->providerStatus)->toBe('pending')
        ->and($response->provider)->toBe(PaymentProviderEnum::KKIAPAY);
});

it('lève une RuntimeException si transactionId absent de la réponse charge', function (): void {
    Http::fake(['sandbox-api.kkiapay.me/*' => Http::response(['error' => 'invalid'], 200)]);

    $request = new \App\Data\Payments\PaymentRequestData(
        country: 'SN',
        currency: CurrencyEnum::XOF,
        method: \App\Enums\PaymentMethodEnum::MOBILE_MONEY,
        amount: 1000,
        idempotencyKey: 'idem_005',
        metadata: ['customer_phone' => '22961000000'],
    );

    expect(fn() => $this->provider->charge($request))
        ->toThrow(RuntimeException::class, 'Kkiapay charge response missing transactionId.');
});

// ─── verifyWebhook ────────────────────────────────────────────────────────

it('retourne true si signature correspond au webhook_secret', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: 'test_webhook_secret',
    );
    expect($this->provider->verifyWebhook($verification))->toBeTrue();
});

it('retourne false si signature incorrecte', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: 'wrong_secret',
    );
    expect($this->provider->verifyWebhook($verification))->toBeFalse();
});

it('retourne false si signature absente', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: '{}',
        payload: [],
        headers: [],
        signature: null,
    );
    expect($this->provider->verifyWebhook($verification))->toBeFalse();
});

// ─── normalizeWebhook ─────────────────────────────────────────────────────

it('normalise transaction.success correctement', function (): void {
    $payload = [
        'transactionId' => 'kkp_tx_005',
        'event'         => 'transaction.success',
        'amount'        => 10000,
        'stateData'     => ['booking_id' => '99'],
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    $event = $this->provider->normalizeWebhook($verification);

    // F-019 — eventId unique
    expect($event->eventId)->toBe('kkiapay_kkp_tx_005_transaction.success')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->amount)->toBe(10000)
        ->and($event->currency)->toBe(CurrencyEnum::XOF)
        ->and($event->metadata)->toBe(['booking_id' => '99']);
});

it('normalise transaction.failed correctement', function (): void {
    $payload = [
        'transactionId' => 'kkp_tx_006',
        'event'         => 'transaction.failed',
        'amount'        => 5000,
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventId)->toBe('kkiapay_kkp_tx_006_transaction.failed')
        ->and($event->eventType)->toBe('transaction.failed')
        ->and($event->providerStatus)->toBe('failed');
});

// F-019 — pending et success ont des eventIds différents
it('génère des eventIds différents pour pending et success sur même transactionId (F-019)', function (): void {
    $txId = 'kkp_shared_tx';

    $pendingEvent = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: '{}',
        payload: ['transactionId' => $txId, 'event' => 'transaction.pending', 'amount' => 1000],
        headers: [],
    ));

    $successEvent = $this->provider->normalizeWebhook(new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: '{}',
        payload: ['transactionId' => $txId, 'event' => 'transaction.success', 'amount' => 1000],
        headers: [],
    ));

    expect($pendingEvent->eventId)->not->toBe($successEvent->eventId)
        ->and($pendingEvent->eventId)->toBe('kkiapay_kkp_shared_tx_transaction.pending')
        ->and($successEvent->eventId)->toBe('kkiapay_kkp_shared_tx_transaction.success');
});

// Statut inconnu → payment.unknown, pas d'exception
it('gère un event inconnu sans exception — retourne payment.unknown (conseil Pavel)', function (): void {
    $payload = [
        'transactionId' => 'kkp_tx_unknown',
        'event'         => 'some.new.event.from.kkiapay',
        'amount'        => 5000,
    ];

    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: json_encode($payload),
        payload: $payload,
        headers: [],
    );

    $event = $this->provider->normalizeWebhook($verification);

    expect($event->eventType)->toBe('payment.unknown')
        ->and($event->providerStatus)->toBe('unknown')
        ->and($event->eventId)->toBe('kkiapay_kkp_tx_unknown_some.new.event.from.kkiapay');
});

it('lève une exception si transactionId absent du webhook', function (): void {
    $verification = new WebhookVerificationData(
        provider: PaymentProviderEnum::KKIAPAY,
        rawBody: '{}',
        payload: ['event' => 'transaction.success'],
        headers: [],
    );

    expect(fn() => $this->provider->normalizeWebhook($verification))
        ->toThrow(RuntimeException::class, 'Kkiapay webhook missing transactionId.');
});

// ─── name ─────────────────────────────────────────────────────────────────

it('retourne le bon nom provider', function (): void {
    expect($this->provider->name())->toBe('kkiapay');
});
