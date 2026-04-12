<?php

use App\Jobs\SendSlackAlert;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('retourne success si aucun seuil critique nest atteint', function () {
    Queue::fake();

    WebhookLog::create([
        'event_id' => 'evt_ok',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'txn_ok',
        'status' => WebhookLog::STATUS_PROCESSED,
        'payload' => [],
        'processed_at' => now(),
    ]);

    $this->artisan('monitoring:webhooks')
        ->expectsOutput('✅ Aucun problème critique détecté.')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('déclenche une alerte si le seuil d échecs webhook est dépassé', function () {
    Queue::fake();
    Mail::fake();

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Alerte monitoring webhook : seuil d’échecs dépassé'
                && $context['window_minutes'] === 10
                && $context['failed_threshold'] === 5
                && $context['failed_count'] === 5;
        });

    config()->set('payment.webhook.alert_email', 'admin@test.com');

    foreach (range(1, 5) as $i) {
        WebhookLog::create([
            'event_id' => "evt_failed_{$i}",
            'event' => 'refund.completed',
            'provider_transaction_id' => "txn_failed_{$i}",
            'status' => WebhookLog::STATUS_FAILED,
            'payload' => [],
            'processed_at' => now(),
        ]);
    }

    $this->artisan('monitoring:webhooks --failed-threshold=5')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Alerte monitoring webhook : seuil d’échecs dépassé'
            && $job->level === 'critical'
            && $job->context['failed_count'] === 5
            && $job->context['failed_threshold'] === 5;
    });
});

it('déclenche une alerte si des failed_jobs webhook existent', function () {
    Queue::fake();
    Mail::fake();

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Alerte monitoring webhook : seuil d’échecs dépassé'
                && $context['failed_jobs_count'] === 1;
        });

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => 'App\\Jobs\\ProcessPaymentWebhook',
        ]),
        'exception' => 'test',
        'failed_at' => now(),
    ]);

    $this->artisan('monitoring:webhooks')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Alerte monitoring webhook : seuil d’échecs dépassé'
            && $job->level === 'critical'
            && $job->context['failed_jobs_count'] === 1;
    });
});
