<?php

use App\Services\Monitoring\QueueHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('collecte la taille des queues redis et les failed jobs récents', function () {
    Redis::del('queues:high', 'queues:default', 'queues:low');

    Redis::rpush('queues:high', json_encode(['job' => 'JobA']));
    Redis::rpush('queues:high', json_encode(['job' => 'JobB']));
    Redis::rpush('queues:default', json_encode(['job' => 'JobC']));
    Redis::rpush('queues:low', json_encode(['job' => 'JobD']));
    Redis::rpush('queues:low', json_encode(['job' => 'JobE']));
    Redis::rpush('queues:low', json_encode(['job' => 'JobF']));

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
            'failed_at' => now()->subMinutes(10),
        ],
    ]);

    $service = app(QueueHealthService::class);

    $metrics = $service->collect(15);

    expect($metrics['queues']['high'])->toBe(2)
        ->and($metrics['queues']['default'])->toBe(1)
        ->and($metrics['queues']['low'])->toBe(3)
        ->and($metrics['failed_jobs_recent'])->toBe(2);

    Redis::del('queues:high', 'queues:default', 'queues:low');
});

it('classifie une pression de capacité quand backlog et age high dépassent les seuils sans retry storm', function () {
    $service = app(\App\Services\Monitoring\QueueHealthService::class);

    $metrics = [
        'queues' => [
            'high' => 40,
            'default' => 0,
            'low' => 0,
        ],
        'oldest_job_age_seconds' => [
            'high' => 90,
            'default' => null,
            'low' => null,
        ],
        'failed_jobs_recent' => 0,
    ];

    $retryStorm = [
        'storm_detected' => false,
        'dominant_job' => null,
        'dominant_count' => 0,
        'counts' => [],
    ];

    $assessment = $service->assessHighQueuePressure(
        $metrics,
        $retryStorm,
        25,
        30
    );

    expect($assessment['status'])->toBe('capacity_pressure')
        ->and($assessment['backlog_exceeded'])->toBeTrue()
        ->and($assessment['age_exceeded'])->toBeTrue()
        ->and($assessment['retry_storm_detected'])->toBeFalse();
});

it('classifie une retry storm pressure quand backlog high dépasse le seuil avec retry storm détecté', function () {
    $service = app(\App\Services\Monitoring\QueueHealthService::class);

    $metrics = [
        'queues' => [
            'high' => 40,
            'default' => 0,
            'low' => 0,
        ],
        'oldest_job_age_seconds' => [
            'high' => 90,
            'default' => null,
            'low' => null,
        ],
        'failed_jobs_recent' => 10,
    ];

    $retryStorm = [
        'storm_detected' => true,
        'dominant_job' => 'App\\Jobs\\ProcessPaymentWebhook',
        'dominant_count' => 12,
        'counts' => [
            'App\\Jobs\\ProcessPaymentWebhook' => 12,
        ],
    ];

    $assessment = $service->assessHighQueuePressure(
        $metrics,
        $retryStorm,
        25,
        30
    );

    expect($assessment['status'])->toBe('retry_storm_pressure')
        ->and($assessment['backlog_exceeded'])->toBeTrue()
        ->and($assessment['retry_storm_detected'])->toBeTrue();
});

it('classifie un slow processing quand age high dépasse le seuil sans backlog élevé', function () {
    $service = app(\App\Services\Monitoring\QueueHealthService::class);

    $metrics = [
        'queues' => [
            'high' => 3,
            'default' => 0,
            'low' => 0,
        ],
        'oldest_job_age_seconds' => [
            'high' => 120,
            'default' => null,
            'low' => null,
        ],
        'failed_jobs_recent' => 0,
    ];

    $retryStorm = [
        'storm_detected' => false,
        'dominant_job' => null,
        'dominant_count' => 0,
        'counts' => [],
    ];

    $assessment = $service->assessHighQueuePressure(
        $metrics,
        $retryStorm,
        25,
        30
    );

    expect($assessment['status'])->toBe('slow_processing')
        ->and($assessment['backlog_exceeded'])->toBeFalse()
        ->and($assessment['age_exceeded'])->toBeTrue();
});

it('classifie un traffic spike quand backlog high dépasse le seuil sans age élevé ni retry storm', function () {
    $service = app(\App\Services\Monitoring\QueueHealthService::class);

    $metrics = [
        'queues' => [
            'high' => 30,
            'default' => 0,
            'low' => 0,
        ],
        'oldest_job_age_seconds' => [
            'high' => 5,
            'default' => null,
            'low' => null,
        ],
        'failed_jobs_recent' => 0,
    ];

    $retryStorm = [
        'storm_detected' => false,
        'dominant_job' => null,
        'dominant_count' => 0,
        'counts' => [],
    ];

    $assessment = $service->assessHighQueuePressure(
        $metrics,
        $retryStorm,
        25,
        30
    );

    expect($assessment['status'])->toBe('traffic_spike')
        ->and($assessment['backlog_exceeded'])->toBeTrue()
        ->and($assessment['age_exceeded'])->toBeFalse()
        ->and($assessment['retry_storm_detected'])->toBeFalse();
});

it('classifie healthy quand aucun signal critique n est dépassé', function () {
    $service = app(\App\Services\Monitoring\QueueHealthService::class);

    $metrics = [
        'queues' => [
            'high' => 2,
            'default' => 0,
            'low' => 0,
        ],
        'oldest_job_age_seconds' => [
            'high' => 3,
            'default' => null,
            'low' => null,
        ],
        'failed_jobs_recent' => 0,
    ];

    $retryStorm = [
        'storm_detected' => false,
        'dominant_job' => null,
        'dominant_count' => 0,
        'counts' => [],
    ];

    $assessment = $service->assessHighQueuePressure(
        $metrics,
        $retryStorm,
        25,
        30
    );

    expect($assessment['status'])->toBe('healthy')
        ->and($assessment['backlog_exceeded'])->toBeFalse()
        ->and($assessment['age_exceeded'])->toBeFalse()
        ->and($assessment['retry_storm_detected'])->toBeFalse();
});
