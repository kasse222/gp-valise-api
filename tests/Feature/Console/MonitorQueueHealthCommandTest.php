<?php

use App\Jobs\SendSlackAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // On nettoie Redis avant chaque test pour éviter
    // qu'un backlog précédent pollue les assertions.
    Redis::del('queues:high', 'queues:default', 'queues:low');
});

afterEach(function () {
    // Même nettoyage après test pour garder une suite stable.
    Redis::del('queues:high', 'queues:default', 'queues:low');
});

it('retourne success si aucun seuil critique nest atteint', function () {
    Queue::fake();

    $this->artisan('monitoring:queues --high-threshold=25 --failed-jobs-threshold=5')
        ->expectsOutput('Queue monitoring snapshot')
        ->expectsOutput('high: 0')
        ->expectsOutput('default: 0')
        ->expectsOutput('low: 0')
        ->expectsOutput('failed_jobs_recent: 0')
        ->expectsOutput('✅ Aucun problème critique détecté.')
        ->assertSuccessful();

    // Aucun job d’alerte ne doit partir si le système est sain.
    Queue::assertNothingPushed();
});

it('déclenche une alerte si le backlog high dépasse le seuil', function () {
    Queue::fake();

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Alerte supervision queues : seuil critique dépassé'
                && $context['queues']['high'] === 3
                && $context['high_threshold'] === 2;
        });

    // On simule un backlog high artificiel.
    Redis::rpush('queues:high', json_encode(['job' => 'A']));
    Redis::rpush('queues:high', json_encode(['job' => 'B']));
    Redis::rpush('queues:high', json_encode(['job' => 'C']));

    $this->artisan('monitoring:queues --high-threshold=2 --failed-jobs-threshold=5')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();

    // L’alerte doit partir en async via SendSlackAlert.
    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Alerte supervision queues : seuil critique dépassé'
            && $job->level === 'critical'
            && $job->context['queues']['high'] === 3
            && $job->context['high_threshold'] === 2;
    });
});

it('déclenche une alerte si le nombre de failed jobs récents dépasse le seuil', function () {
    Queue::fake();

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Alerte supervision queues : seuil critique dépassé'
                && $context['failed_jobs_recent'] === 2
                && $context['failed_jobs_threshold'] === 2;
        });

    DB::table('failed_jobs')->insert([
        [
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'high',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\ProcessPaymentWebhook']),
            'exception' => 'Test exception 1',
            'failed_at' => now(),
        ],
        [
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\OtherJob']),
            'exception' => 'Test exception 2',
            'failed_at' => now(),
        ],
    ]);

    $this->artisan('monitoring:queues --high-threshold=25 --failed-jobs-threshold=2')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Alerte supervision queues : seuil critique dépassé'
            && $job->level === 'critical'
            && $job->context['failed_jobs_recent'] === 2;
    });
});

it('déclenche une alerte si le plus vieux job high dépasse le seuil d ancienneté', function () {
    Queue::fake();

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Alerte supervision queues : seuil critique dépassé'
                && $context['high_age_threshold'] === 30
                && $context['oldest_job_age_seconds']['high'] >= 30;
        });

    // On injecte un faux job ancien dans la queue high.
    Redis::rpush('queues:high', json_encode([
        'uuid' => (string) Str::uuid(),
        'displayName' => 'App\\Jobs\\FakeHighJob',
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'maxTries' => null,
        'maxExceptions' => null,
        'failOnTimeout' => false,
        'backoff' => null,
        'timeout' => null,
        'retryUntil' => null,
        'data' => [],
        'pushedAt' => now()->subSeconds(45)->timestamp,
    ]));

    $this->artisan('monitoring:queues --high-threshold=25 --failed-jobs-threshold=99 --high-age-threshold=30')
        ->expectsOutput('⚠️ Alerte déclenchée.')
        ->assertFailed();

    Queue::assertPushed(SendSlackAlert::class, function (SendSlackAlert $job) {
        return $job->message === 'Alerte supervision queues : seuil critique dépassé'
            && $job->context['oldest_job_age_seconds']['high'] >= 30;
    });
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
