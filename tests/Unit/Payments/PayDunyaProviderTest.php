<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use App\Services\Payments\PayDunyaProvider;
use Illuminate\Support\Facades\Config;

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

it('verifyWebhook retourne true si hash SHA-512 master key valide', function () {
    Config::set('payment_providers.paydunya.master_key', 'test-master-key');

    $payload = [
        'hash' => hash('sha512', 'test-master-key'),
        'status' => 'completed',
        'invoice' => [
            'token' => 'test_token',
            'total_amount' => 10000,
        ],
    ];

    expect((new PayDunyaProvider())->verifyWebhook(paydunyaWebhookData($payload)))->toBeTrue();
});

it('verifyWebhook supporte le payload PayDunya sous data', function () {
    Config::set('payment_providers.paydunya.master_key', 'test-master-key');

    $payload = [
        'data' => [
            'hash' => hash('sha512', 'test-master-key'),
            'status' => 'completed',
            'invoice' => [
                'token' => 'test_token',
                'total_amount' => 10000,
            ],
        ],
    ];

    expect((new PayDunyaProvider())->verifyWebhook(paydunyaWebhookData($payload)))->toBeTrue();
});

it('verifyWebhook retourne false si hash invalide', function () {
    Config::set('payment_providers.paydunya.master_key', 'test-master-key');

    $payload = [
        'hash' => 'invalid-hash',
    ];

    expect((new PayDunyaProvider())->verifyWebhook(paydunyaWebhookData($payload)))->toBeFalse();
});

it('verifyWebhook retourne false si hash absent', function () {
    $payload = [
        'status' => 'completed',
    ];

    expect((new PayDunyaProvider())->verifyWebhook(paydunyaWebhookData($payload)))->toBeFalse();
});

it('normalizeWebhook mappe completed vers transaction.success', function () {
    $payload = [
        'hash' => 'ignored',
        'status' => 'completed',
        'invoice' => [
            'token' => 'test_invoice_token',
            'total_amount' => 15000,
        ],
        'custom_data' => [
            'booking_id' => 123,
            'user_id' => 456,
        ],
    ];

    $event = (new PayDunyaProvider())->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->provider)->toBe(PaymentProviderEnum::PAYDUNYA)
        ->and($event->eventId)->toBe('test_invoice_token')
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
            'hash' => 'ignored',
            'status' => 'completed',
            'invoice' => [
                'token' => 'test_data_token',
                'total_amount' => 20000,
            ],
            'custom_data' => [
                'booking_id' => 777,
            ],
        ],
    ];

    $event = (new PayDunyaProvider())->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->eventId)->toBe('test_data_token')
        ->and($event->eventType)->toBe('transaction.success')
        ->and($event->amount)->toBe(20000)
        ->and($event->metadata['booking_id'])->toBe(777);
});

it('normalizeWebhook mappe cancelled vers transaction.failed', function () {
    $payload = [
        'status' => 'cancelled',
        'invoice' => [
            'token' => 'cancelled_token',
            'total_amount' => 10000,
        ],
    ];

    $event = (new PayDunyaProvider())->normalizeWebhook(paydunyaWebhookData($payload));

    expect($event->eventType)->toBe('transaction.failed')
        ->and($event->providerStatus)->toBe('failed');
});

it('normalizeWebhook lève une exception si token absent', function () {
    $payload = [
        'status' => 'completed',
        'invoice' => [
            'total_amount' => 10000,
        ],
    ];

    expect(fn() => (new PayDunyaProvider())->normalizeWebhook(paydunyaWebhookData($payload)))
        ->toThrow(RuntimeException::class, 'PayDunya webhook missing token.');
});

it('refund retourne pending_manual pour traitement manuel PayDunya', function () {
    $response = (new PayDunyaProvider())->refund(new RefundRequestData(
        provider: PaymentProviderEnum::PAYDUNYA,
        providerTransactionId: 'paydunya_token_123',
        amount: 10000,
        currency: CurrencyEnum::XOF,
        idempotencyKey: 'refund-1',
        reason: 'Client refund',
        metadata: [
            'booking_id' => 1,
        ],
    ));

    expect($response->provider)->toBe(PaymentProviderEnum::PAYDUNYA)
        ->and($response->providerTransactionId)->toBe('paydunya_token_123')
        ->and($response->providerStatus)->toBe('pending_manual')
        ->and($response->amount)->toBe(10000)
        ->and($response->currency)->toBe(CurrencyEnum::XOF)
        ->and($response->rawPayload['manual_required'])->toBeTrue();
});

it('name retourne paydunya', function () {
    expect((new PayDunyaProvider())->name())->toBe(PaymentProviderEnum::PAYDUNYA->value);
});
