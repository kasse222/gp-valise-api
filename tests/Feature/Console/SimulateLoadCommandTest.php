<?php

use App\Jobs\SimulateHeavyJob;
use App\Services\Monitoring\QueueProtectionService;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class);

it('bloque le dispatch de simulate:load si un retry storm est détecté', function () {
    Queue::fake();

    $guardPayload = [
        'allowed' => false,
        'blocked' => true,
        'reason' => 'Retry storm détecté sur la fenêtre récente.',
        'recommended_action' => 'Suspendre temporairement les nouveaux dispatchs sur la queue high et corriger le job dominant.',
        'retry_storm' => [
            'dominant_job' => 'App\\Jobs\\SimulateRetryStormJob',
            'dominant_count' => 10,
        ],
    ];

    $mock = \Mockery::mock(QueueProtectionService::class);
    $mock->shouldReceive('guardHighQueueDispatch')
        ->once()
        ->with(15, 5)
        ->andReturn($guardPayload);

    app()->instance(QueueProtectionService::class, $mock);

    $this->artisan('simulate:load', [
        '--jobs' => 10,
        '--duration' => 1,
    ])
        ->expectsOutput('⛔ Dispatch bloqué sur la queue high.')
        ->expectsOutput('Reason: Retry storm détecté sur la fenêtre récente.')
        ->expectsOutput('Recommended action: Suspendre temporairement les nouveaux dispatchs sur la queue high et corriger le job dominant.')
        ->expectsOutput('Dominant job: App\Jobs\SimulateRetryStormJob')
        ->expectsOutput('Dominant count: 10')
        ->assertFailed();

    Queue::assertNotPushed(SimulateHeavyJob::class);
});

it('autorise le dispatch de simulate:load si aucun retry storm n’est détecté', function () {
    Queue::fake();

    $guardPayload = [
        'allowed' => true,
        'blocked' => false,
        'reason' => 'Aucun retry storm détecté.',
        'recommended_action' => 'Dispatch autorisé.',
        'retry_storm' => [
            'dominant_job' => null,
            'dominant_count' => 0,
        ],
    ];

    $mock = \Mockery::mock(QueueProtectionService::class);
    $mock->shouldReceive('guardHighQueueDispatch')
        ->once()
        ->with(15, 5)
        ->andReturn($guardPayload);

    app()->instance(QueueProtectionService::class, $mock);

    $this->artisan('simulate:load', [
        '--jobs' => 10,
        '--duration' => 1,
    ])
        ->expectsOutput('Dispatching 10 jobs on queue high (duration: 1s)...')
        ->expectsOutput('Load simulation dispatched successfully.')
        ->assertSuccessful();

    Queue::assertPushed(SimulateHeavyJob::class, 10);
});
