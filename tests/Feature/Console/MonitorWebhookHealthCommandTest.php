<?php

use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('retourne success si aucun seuil critique nest atteint', function () {
    WebhookLog::query()->create([
        'event_id' => 'evt_ok_1',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'txn_ok_1',
        'status' => WebhookLog::STATUS_PROCESSED,
        'payload' => ['foo' => 'bar'],
        'processed_at' => now(),
    ]);

    $this->artisan('monitoring:webhooks --minutes=10 --failed-threshold=5')
        ->expectsOutput('Webhook monitoring sur les 10 dernières minutes')
        ->expectsOutput('✅ Aucun problème critique détecté.')
        ->assertSuccessful();
});

it('déclenche une alerte si le seuil déchecs webhook est dépassé', function () {
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

    config()->set('payment.webhook.alert_email', 'admin@gp-valise.com');

    foreach (range(1, 5) as $i) {
        WebhookLog::query()->create([
            'event_id' => "evt_failed_{$i}",
            'event' => 'refund.completed',
            'provider_transaction_id' => "txn_failed_{$i}",
            'status' => WebhookLog::STATUS_FAILED,
            'payload' => ['foo' => 'bar'],
            'error_message' => 'Simulated failure',
            'processed_at' => now(),
        ]);
    }

    $this->artisan('monitoring:webhooks --minutes=10 --failed-threshold=5')
        ->expectsOutput('Webhook monitoring sur les 10 dernières minutes')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();
});

it('déclenche une alerte si des failed_jobs webhook existent', function () {
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
        'exception' => 'Test exception',
        'failed_at' => now(),
    ]);

    $this->artisan('monitoring:webhooks --minutes=10 --failed-threshold=99')
        ->expectsOutput('Webhook monitoring sur les 10 dernières minutes')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();
});
