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
        'event_id'                => 'evt_job_123',
        'event'                   => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_job_123',
    ];

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload, null);

    $job = new ProcessPaymentWebhook($payload);
    $job->handle($actionMock);
});

// F-011 — le job laisse toujours remonter l'exception retryable
// Laravel gère les retries via tries/backoff ; on ne l'avale plus jamais
it('relance toujours une exception retryable quelle que soit la tentative', function () {
    $payload = [
        'event_id'                => 'evt_retry_job',
        'event'                   => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_job',
    ];

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload, null)
        ->andThrow(new RetryableWebhookException('Transaction introuvable'));

    $job = new ProcessPaymentWebhook($payload);

    expect(fn() => $job->handle($actionMock))
        ->toThrow(RetryableWebhookException::class, 'Transaction introuvable');
});

// F-011 — même au 3ème essai, l'exception remonte (failed() sera appelé par Laravel)
it('relance l\'exception retryable même à la 3ème tentative (F-011)', function () {
    $payload = [
        'event_id'                => 'evt_retry_job_stop',
        'event'                   => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_job_stop',
    ];

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload, null)
        ->andThrow(new RetryableWebhookException('Transaction introuvable'));

    $job = new ProcessPaymentWebhook($payload);

    // Doit toujours lever l'exception — plus de Log::warning silencieux
    expect(fn() => $job->handle($actionMock))
        ->toThrow(RetryableWebhookException::class, 'Transaction introuvable');
});

it('transmet le correlationId à HandlePaymentWebhook::execute()', function () {
    $payload = [
        'event_id'                => 'evt_cid_propagation',
        'event'                   => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_cid_propagation',
    ];
    $correlationId = 'cid-propagated-test-001';

    $actionMock = Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload, $correlationId);

    $job = new ProcessPaymentWebhook($payload, $correlationId);
    $job->handle($actionMock);
});

it('journalise une erreur critique et dispatch une alerte Slack quand le job échoue définitivement', function () {
    Queue::fake();

    $payload = [
        'event_id'                => 'evt_failed_job',
        'event'                   => 'refund.completed',
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
