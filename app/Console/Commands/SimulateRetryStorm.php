<?php

namespace App\Console\Commands;

use App\Jobs\SimulateRetryStormJob;
use Illuminate\Console\Command;

class SimulateRetryStorm extends Command
{
    protected $signature = 'simulate:retry-storm {--jobs=20} {--duration=1}';
    protected $description = 'Dispatch failing jobs to simulate a retry storm on queue high';

    public function handle(): int
    {
        $jobs = (int) $this->option('jobs');
        $duration = (int) $this->option('duration');

        $this->warn("Dispatching {$jobs} failing jobs on queue high (duration: {$duration}s)...");

        for ($i = 0; $i < $jobs; $i++) {
            SimulateRetryStormJob::dispatch($duration);
        }

        $this->info('Retry storm simulation dispatched.');

        return self::SUCCESS;
    }
}
