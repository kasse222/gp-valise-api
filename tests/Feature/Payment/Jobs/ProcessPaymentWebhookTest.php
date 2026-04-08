<?php

use App\Actions\Payment\HandlePaymentWebhook;
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

    $this->app->instance(HandlePaymentWebhook::class, $actionMock);

    $job = new ProcessPaymentWebhook($payload);
    $job->handle($actionMock);
});
