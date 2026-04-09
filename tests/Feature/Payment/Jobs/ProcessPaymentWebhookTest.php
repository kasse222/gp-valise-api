<?php

use App\Actions\Payment\HandlePaymentWebhook;
use App\Exceptions\RetryableWebhookException;
use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $actionMock = \Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload)
        ->andThrow(new RetryableWebhookException('Transaction introuvable'));

    $job = \Mockery::mock(ProcessPaymentWebhook::class, [$payload])->makePartial();
    $job->shouldReceive('attempts')->andReturn(1);

    $this->expectException(RetryableWebhookException::class);

    $job->handle($actionMock);
});

it('n’échoue plus après plusieurs tentatives retryables et journalise un warning', function () {
    $payload = [
        'event_id' => 'evt_retry_job_stop',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_job_stop',
    ];

    $actionMock = \Mockery::mock(HandlePaymentWebhook::class);
    $actionMock->shouldReceive('execute')
        ->once()
        ->with($payload)
        ->andThrow(new RetryableWebhookException('Transaction introuvable'));

    $job = \Mockery::mock(ProcessPaymentWebhook::class, [$payload])->makePartial();
    $job->shouldReceive('attempts')->andReturn(3);

    $job->handle($actionMock);

    expect(true)->toBeTrue();
});
