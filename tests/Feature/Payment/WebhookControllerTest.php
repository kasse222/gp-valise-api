<?php

use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('dispatch un job pour traiter le webhook paiement', function () {
    Queue::fake();

    $payload = [
        'event_id' => 'evt_123',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_123',
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
        ->assertJson([
            'status' => 'accepted',
        ]);

    Queue::assertPushed(ProcessPaymentWebhook::class, function (ProcessPaymentWebhook $job) use ($payload) {
        return $job->payload['event_id'] === $payload['event_id']
            && $job->payload['event'] === $payload['event']
            && $job->payload['provider_transaction_id'] === $payload['provider_transaction_id'];
    });
});

it('rejette le webhook si la signature est invalide et ne dispatch aucun job', function () {
    Queue::fake();

    $payload = [
        'event_id' => 'evt_invalid_sig',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_invalid',
    ];

    $response = $this->postJson('/api/v1/webhooks/payment', $payload, [
        'X-Signature' => 'invalid_signature',
    ]);

    $response->assertForbidden();

    Queue::assertNothingPushed();
});
