<?php

namespace App\Console\Commands;

use App\Jobs\SimulateHeavyJob;
use App\Services\Monitoring\QueueProtectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SimulateLoad extends Command
{
    protected $signature = 'simulate:load
        {--jobs=1000 : Number of jobs to dispatch}
        {--duration=1 : Duration in seconds for each simulated job}
        {--guard-window=15 : Retry storm analysis window in minutes}
        {--guard-threshold=5 : Retry storm dominant job threshold}';

    protected $description = 'Simulate heavy load on queue high';

    public function __construct(
        private readonly QueueProtectionService $queueProtectionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $jobs = (int) $this->option('jobs');
        $duration = (int) $this->option('duration');
        $guardWindow = (int) $this->option('guard-window');
        $guardThreshold = (int) $this->option('guard-threshold');

        $guard = $this->queueProtectionService->guardHighQueueDispatch(
            windowMinutes: $guardWindow,
            perJobThreshold: $guardThreshold,
        );

        if (! $guard['allowed']) {
            $this->warn('⛔ Dispatch bloqué sur la queue high.');
            $this->line('Reason: ' . $guard['reason']);
            $this->line('Recommended action: ' . $guard['recommended_action']);
            $this->line('Dominant job: ' . ($guard['retry_storm']['dominant_job'] ?? 'none'));
            $this->line('Dominant count: ' . ($guard['retry_storm']['dominant_count'] ?? 0));

            Log::warning('Dispatch simulate:load bloqué par QueueProtectionService', [
                'jobs' => $jobs,
                'duration' => $duration,
                'guard' => $guard,
                'command' => static::class,
            ]);

            return self::FAILURE;
        }

        $this->info("Dispatching {$jobs} jobs on queue high (duration: {$duration}s)...");

        for ($i = 0; $i < $jobs; $i++) {
            SimulateHeavyJob::dispatch($duration)->onQueue('high');
        }

        $this->info('Load simulation dispatched successfully.');

        return self::SUCCESS;
    }
}
