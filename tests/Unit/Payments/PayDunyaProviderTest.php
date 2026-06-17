<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use App\Payments\Mappers\PayDunyaStatusMapper;
use App\Services\Payments\PayDunyaProvider;
use Illuminate\Support\Facades\Config;

// Helper — instanciation avec mapper injecté
function makePayDunya(): PayDunyaProvider
{
    return new PayDunyaProvider(new PayDunyaStatusMapper());
}

function paydunyaWebhookData(array $payload): WebhookVerificationData
{
    return new WebhookVerificationData(
        provider: PaymentProviderEnum::PAYDUNYA,
        rawBody: json_encode($payload, JSON_THROW_ON_ERROR),
        payload: $payload,
        headers: [],
        signature: null,
        eventId: null,
        correlationId: null,
    );
}

// ─── verifyWebhook ────────────────────────────────────────────────────────

it('verifyWebhook retourne true si hash SHA-512 master key valide', function () {
    Config::set('payment_providers.paydunya.master_key', 'test-master-key');
    $payload = [
        'hash' => hash('sha512', 'test-master-key'),
        'status' => 'completed',
        'invoice' => ['token' => 'test_token', 'total_amount' => 10000],
    ];
    expect(makePayDunya()->verifyWebhook(paydunyaWebhookData($payload)))->toBeTrue();
});

it('verifyWebhook supporte le payload PayDunya sous data', function () {
    Config::set('payment_providers.paydunya.master_key', 'test-master-key');
    $payload = [
        'data' => [
            'hash' => hash('sha512', 'test-master-key'),
            'status' => 'completed',
            'invoice' => ['token' => 'test_token', 'total_amount' => 10000],
        ],
    ];
    expect(makePayDunya()->verifyWebhook(paydunyaWebhookData($payload)))->toBeTrue();
});

it('verifyWebhook retourne false si hash invalide', function () {
    Config::set('payment_providers.paydunya.master_key', 'test-master-key');
    $payload = ['hash' => 'invalid-hash'];
    expect(makePayDunya()->verifyWebhook(paydunyaWebhookData($payload)))->toBeFalse();
});

it('verifyWebhook retourne false si hash absent', function () {
    $payload = ['status' => 'completed'];
    expect(makePayDunya()->verifyWebhook(paydunyaWebhookData($payload)))->toBeFalse();
});

// ─── normalizeWebhook ─────────────────────────────────────────────────────

it('normalizeWebhook mappe completed vers transaction.success', function () {
    $payload = [
        'status'  => 'completed',
        'invoice' => ['token' => 'test_invoice_token', 'total_amount' => 15000],
        'custom_data' => ['booking_id' => 123, 'user_id' => 456],
    ];

    $event = makePayDunya()->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->provider)->toBe(PaymentProviderEnum::PAYDUNYA)
        // F-019 — eventId unique : provider + token + status
        ->and($event->eventId)->toBe('paydunya_test_invoice_token_completed')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->providerTransactionId)->toBe('test_invoice_token')
        ->and($event->providerStatus)->toBe('completed')
        ->and($event->amount)->toBe(15000)
        ->and($event->currency)->toBe(CurrencyEnum::XOF)
        ->and($event->metadata['booking_id'])->toBe(123);
});

it('normalizeWebhook supporte le payload data PayDunya', function () {
    $payload = [
        'data' => [
            'status'  => 'completed',
            'invoice' => ['token' => 'test_data_token', 'total_amount' => 20000],
            'custom_data' => ['booking_id' => 777],
        ],
    ];

    $event = makePayDunya()->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->eventId)->toBe('paydunya_test_data_token_completed')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->amount)->toBe(20000)
        ->and($event->metadata['booking_id'])->toBe(777);
});

it('normalizeWebhook mappe cancelled vers transaction.failed', function () {
    $payload = [
        'status'  => 'cancelled',
        'invoice' => ['token' => 'cancelled_token', 'total_amount' => 10000],
    ];

    $event = makePayDunya()->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->eventType)->toBe('transaction.failed')
        ->and($event->providerStatus)->toBe('cancelled')
        ->and($event->eventId)->toBe('paydunya_cancelled_token_cancelled');
});

it('normalizeWebhook lève une exception si token absent', function () {
    $payload = [
        'status'  => 'completed',
        'invoice' => ['total_amount' => 10000],
    ];

    expect(fn() => makePayDunya()->normalizeWebhook(paydunyaWebhookData($payload)))
        ->toThrow(RuntimeException::class, 'PayDunya webhook missing token.');
});

// F-019 — eventIds différents pour pending et completed sur même token
it('normalizeWebhook génère des eventIds différents pour pending et completed (F-019)', function () {
    $token = 'same_token_xyz';

    $pendingPayload = [
        'status'  => 'pending',
        'invoice' => ['token' => $token, 'total_amount' => 10000],
    ];
    $completedPayload = [
        'status'  => 'completed',
        'invoice' => ['token' => $token, 'total_amount' => 10000],
    ];

    $pending   = makePayDunya()->normalizeWebhook(paydunyaWebhookData($pendingPayload));
    $completed = makePayDunya()->normalizeWebhook(paydunyaWebhookData($completedPayload));

    expect($pending->eventId)->not->toBe($completed->eventId)
        ->and($pending->eventId)->toBe('paydunya_same_token_xyz_pending')
        ->and($completed->eventId)->toBe('paydunya_same_token_xyz_completed');
});

// Statut inconnu — loggué, aucune exception
it('normalizeWebhook gère un statut inconnu sans exception (conseil Pavel)', function () {
    $payload = [
        'status'  => 'super_weird_status_xyz',
        'invoice' => ['token' => 'tok_unknown', 'total_amount' => 5000],
    ];

    $event = makePayDunya()->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->providerStatus)->toBe('unknown')
        ->and($event->eventType)->toBe('transaction.pending') // statut inconnu → pending par défaut
        ->and($event->eventId)->toBe('paydunya_tok_unknown_super_weird_status_xyz');
});

// ─── refund ───────────────────────────────────────────────────────────────

it('refund retourne pending_manual pour traitement manuel PayDunya', function () {
    $response = makePayDunya()->refund(new RefundRequestData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'paydunya_token_123',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-1',
        reason: 'Client refund',
        metadata: ['booking_id' => 1],
    ));

    expect($response->provider)->toBe(PaymentProviderEnum::PAYDUNYA)
        ->and($response->providerTransactionId)->toBe('paydunya_token_123')
        ->and($response->providerStatus)->toBe('pending_manual')
        ->and($response->rawPayload['manual_required'])->toBeTrue();
});

// ─── name ─────────────────────────────────────────────────────────────────

it('name retourne paydunya', function () {
    expect(makePayDunya()->name())->toBe(PaymentProviderEnum::PAYDUNYA->value);
});
