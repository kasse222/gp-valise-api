<?php

declare(strict_types=1);

use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('dispatch un job pour traiter le webhook paiement avec correlation_id', function (): void {
    Queue::fake();

    $payload = [
        'event_id' => 'evt_123',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_123',
    ];

    $correlationId = (string) Str::uuid();

    $signature = hash_hmac(
        'sha256',
        json_encode($payload),
        config('payment.webhook.secret')
    );

    $response = $this->postJson('/api/v1/webhooks/payment', $payload, [
        'X-Signature' => $signature,
        'X-Correlation-ID' => $correlationId,
    ]);

    $response->assertAccepted()
        ->assertHeader('X-Correlation-ID', $correlationId)
        ->assertJson([
            'status' => 'accepted',
        ]);

    Queue::assertPushed(ProcessPaymentWebhook::class, function (ProcessPaymentWebhook $job) use ($payload, $correlationId): bool {
        return $job->payload['event_id'] === $payload['event_id']
            && $job->payload['event'] === $payload['event']
            && $job->payload['provider_transaction_id'] === $payload['provider_transaction_id']
            && $job->correlationId === $correlationId;
    });
});

it('génère un correlation_id si le webhook est reçu sans header dédié', function (): void {
    Queue::fake();

    $payload = [
        'event_id' => 'evt_without_correlation_id',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_without_correlation',
    ];

    $signature = hash_hmac(
        'sha256',
        json_encode($payload),
        config('payment.webhook.secret')
    );

    $response = $this->postJson('/api/v1/webhooks/payment', $payload, [
        'X-Signature' => $signature,
    ]);

    $response->assertAccepted()
        ->assertHeader('X-Correlation-ID');

    $correlationId = $response->headers->get('X-Correlation-ID');

    expect(Str::isUuid($correlationId))->toBeTrue();

    Queue::assertPushed(ProcessPaymentWebhook::class, function (ProcessPaymentWebhook $job) use ($payload, $correlationId): bool {
        return $job->payload['event_id'] === $payload['event_id']
            && $job->correlationId === $correlationId;
    });
});

it('rejette le webhook si la signature est invalide et ne dispatch aucun job', function (): void {
    Queue::fake();

    $payload = [
        'event_id' => 'evt_invalid_sig',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_invalid',
    ];

    $response = $this->postJson('/api/v1/webhooks/payment', $payload, [
        'X-Signature' => 'invalid_signature',
    ]);

    $response->assertForbidden()
        ->assertHeader('X-Correlation-ID');

    Queue::assertNothingPushed();
});
