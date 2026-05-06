<?php

declare(strict_types=1);

use App\Contracts\Payments\WebhookProcessorContract;
use App\Data\Payments\PaymentEventData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->processor = Mockery::mock(WebhookProcessorContract::class);
    $this->app->instance(WebhookProcessorContract::class, $this->processor);
});

it('dispatch un job avec payload normalisé et correlation_id', function (): void {
    Queue::fake();

    $correlationId = (string) Str::uuid();

    $event = new PaymentEventData(
        provider: PaymentProviderEnum::KKIAPAY,
        eventId: 'kkp_evt_001',
        eventType: 'transaction.success',
        providerTransactionId: 'kkp_tx_001',
        providerStatus: 'completed',
        amount: 5000,
        currency: CurrencyEnum::XOF,
        metadata: ['booking_id' => 42],
        rawPayload: ['transactionId' => 'kkp_tx_001'],
    );

    $this->processor
        ->shouldReceive('process')
        ->once()
        ->andReturn($event);

    $response = $this->postJson('/api/v1/webhooks/kkiapay', [], [
        'X-Correlation-ID' => $correlationId,
        'x-kkiapay-secret' => 'test-secret',
    ]);

    $response->assertAccepted()
        ->assertJson(['status' => 'accepted']);

    Queue::assertPushed(ProcessPaymentWebhook::class, function (ProcessPaymentWebhook $job) use ($event, $correlationId): bool {
        return $job->payload['event_id'] === $event->eventId
            && $job->payload['event_type'] === $event->eventType
            && $job->payload['provider'] === $event->provider->value
            && $job->payload['provider_transaction_id'] === $event->providerTransactionId
            && $job->payload['provider_status'] === $event->providerStatus
            && $job->payload['amount'] === $event->amount
            && $job->payload['currency'] === $event->currency->value
            && $job->correlationId === $correlationId;
    });
});

it('génère un correlation_id si absent du header', function (): void {
    Queue::fake();

    $event = new PaymentEventData(
        provider: PaymentProviderEnum::KKIAPAY,
        eventId: 'kkp_evt_002',
        eventType: 'transaction.success',
        providerTransactionId: 'kkp_tx_002',
        providerStatus: 'completed',
        amount: 1000,
        currency: CurrencyEnum::XOF,
    );

    $this->processor
        ->shouldReceive('process')
        ->once()
        ->andReturn($event);

    $response = $this->postJson('/api/v1/webhooks/kkiapay', []);

    $response->assertAccepted();

    Queue::assertPushed(ProcessPaymentWebhook::class, function (ProcessPaymentWebhook $job): bool {
        return $job->correlationId !== null
            && Str::isUuid($job->correlationId);
    });
});

it('retourne 403 si WebhookProcessor lève AccessDeniedHttpException', function (): void {
    Queue::fake();

    $this->processor
        ->shouldReceive('process')
        ->once()
        ->andThrow(new AccessDeniedHttpException('Invalid webhook signature.'));

    $response = $this->postJson('/api/v1/webhooks/kkiapay', [], [
        'x-kkiapay-secret' => 'wrong-secret',
    ]);

    $response->assertForbidden();

    Queue::assertNothingPushed();
});

it('passe le providerKey correct au WebhookProcessor', function (): void {
    Queue::fake();

    $event = new PaymentEventData(
        provider: PaymentProviderEnum::KKIAPAY,
        eventId: 'kkp_evt_003',
        eventType: 'transaction.success',
        providerTransactionId: 'kkp_tx_003',
        providerStatus: 'completed',
        amount: 2000,
        currency: CurrencyEnum::XOF,
    );

    $this->processor
        ->shouldReceive('process')
        ->once()
        ->withArgs(function ($request, string $providerKey): bool {
            return $providerKey === 'kkiapay';
        })
        ->andReturn($event);

    $this->postJson('/api/v1/webhooks/kkiapay', [])
        ->assertAccepted();
});

it('ne dispatch pas si WebhookProcessor lève une exception inattendue', function (): void {
    Queue::fake();

    $this->processor
        ->shouldReceive('process')
        ->once()
        ->andThrow(new \RuntimeException('Unexpected error'));

    $this->postJson('/api/v1/webhooks/kkiapay', [])
        ->assertStatus(500);

    Queue::assertNothingPushed();
});
