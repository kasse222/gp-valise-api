<?php

use App\Jobs\SendSlackAlert;
use App\Models\WebhookLog;
use App\Services\Alerting\SlackNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('retourne success si aucun seuil critique nest atteint', function () {

    $slack = \Mockery::mock(SlackNotifier::class);
    $slack->shouldReceive('send')->never();
    $this->app->instance(SlackNotifier::class, $slack);

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
});

it('déclenche alerte si seuil dépassé', function () {

    Mail::fake();

    $slack = \Mockery::mock(SlackNotifier::class);
    $slack->shouldReceive('send')->once();
    $this->app->instance(SlackNotifier::class, $slack);

    Log::shouldReceive('channel')->once()->andReturnSelf();
    Log::shouldReceive('critical')->once();

    config()->set('payment.webhook.alert_email', 'admin@test.com');

    foreach (range(1, 5) as $i) {
        WebhookLog::create([
            'event_id' => "evt_$i",
            'event' => 'refund.completed',
            'provider_transaction_id' => "txn_$i",
            'status' => WebhookLog::STATUS_FAILED,
            'payload' => [],
            'processed_at' => now(),
        ]);
    }

    $this->artisan('monitoring:webhooks --failed-threshold=5')
        ->assertFailed();
});

it('déclenche alerte si failed_jobs présent', function () {

    Mail::fake();

    $slack = \Mockery::mock(SlackNotifier::class);
    $slack->shouldReceive('send')->once();
    $this->app->instance(SlackNotifier::class, $slack);

    Log::shouldReceive('channel')->once()->andReturnSelf();
    Log::shouldReceive('critical')->once();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\ProcessPaymentWebhook']),
        'exception' => 'test',
        'failed_at' => now(),
    ]);

    $this->artisan('monitoring:webhooks')
        ->assertFailed();
});

it('déclenche une alerte si un retry storm est détecté sur un type de job', function () {
    Queue::fake();

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Alerte supervision queues : seuil critique dépassé'
                && $context['retry_storm']['storm_detected'] === true
                && $context['retry_storm']['dominant_job'] === 'App\\Jobs\\ProcessPaymentWebhook'
                && $context['retry_storm']['dominant_count'] === 5;
        });

    foreach (range(1, 5) as $i) {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'high',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\ProcessPaymentWebhook',
            ]),
            'exception' => "Test exception {$i}",
            'failed_at' => now(),
        ]);
    }

    $this->artisan('monitoring:queues --high-threshold=25 --failed-jobs-threshold=99 --window=15')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Alerte supervision queues : seuil critique dépassé'
            && $job->level === 'critical'
            && $job->context['retry_storm']['storm_detected'] === true
            && $job->context['retry_storm']['dominant_job'] === 'App\\Jobs\\ProcessPaymentWebhook';
    });
});
