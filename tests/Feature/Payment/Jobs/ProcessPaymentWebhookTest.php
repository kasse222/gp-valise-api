<?php

use App\Actions\Payment\HandlePaymentWebhook;
use App\Exceptions\RetryableWebhookException;
use App\Jobs\ProcessPaymentWebhook;
use App\Jobs\SendSlackAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('appelle HandlePaymentWebhook avec le payload fourni', function () {
    $payload = [
        'event_id' => 'evt_job_123',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_job_123',
    ];

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload);

    $job = new ProcessPaymentWebhook($payload);

    $job->handle($actionMock);
});

it('relance une exception retryable si le nombre de tentatives est inférieur au seuil', function () {
    $payload = [
        'event_id' => 'evt_retry_job',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_job',
    ];

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload)
        ->andThrow(new RetryableWebhookException('Transaction introuvable'));

    $job = new class($payload, 1) extends ProcessPaymentWebhook {
        public function __construct(array $payload, private int $fakeAttempts)
        {
            parent::__construct($payload);
        }

        public function attempts(): int
        {
            return $this->fakeAttempts;
        }
    };

    expect(fn() => $job->handle($actionMock))
        ->toThrow(RetryableWebhookException::class);
});

it('n’échoue plus après plusieurs tentatives retryables et journalise un warning', function () {
    $payload = [
        'event_id' => 'evt_retry_job_stop',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_job_stop',
    ];

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload)
        ->andThrow(new RetryableWebhookException('Transaction introuvable'));

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context) use ($payload) {
            return $message === 'Webhook abandonné après plusieurs tentatives retryables'
                && $context['payload'] === $payload
                && $context['attempts'] === 3
                && str_contains($context['job'], ProcessPaymentWebhook::class)
                && $context['error'] === 'Transaction introuvable';
        });

    $job = new class($payload, 3) extends ProcessPaymentWebhook {
        public function __construct(array $payload, private int $fakeAttempts)
        {
            parent::__construct($payload);
        }

        public function attempts(): int
        {
            return $this->fakeAttempts;
        }
    };

    $job->handle($actionMock);

    expect(true)->toBeTrue();
});

it('journalise une erreur critique et dispatch une alerte Slack quand le job échoue définitivement', function () {
    Queue::fake();

    $payload = [
        'event_id' => 'evt_failed_job',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_failed',
    ];

    $exception = new RuntimeException('Erreur définitive webhook');

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) use ($payload) {
            return $message === 'WEBHOOK DEFINITIVEMENT ECHOUE'
                && $context['payload'] === $payload
                && $context['error'] === 'Erreur définitive webhook'
                && $context['job'] === ProcessPaymentWebhook::class;
        });

    $job = new ProcessPaymentWebhook($payload);

    $job->failed($exception);

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $alertJob) use ($payload) {
        return $alertJob->message === 'Webhook définitivement échoué'
            && $alertJob->level === 'critical'
            && $alertJob->context['payload'] === $payload
            && $alertJob->context['error'] === 'Erreur définitive webhook'
            && $alertJob->context['job'] === ProcessPaymentWebhook::class;
    });
});
