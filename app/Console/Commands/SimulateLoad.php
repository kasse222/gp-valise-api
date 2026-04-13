<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SimulateHeavyJob;

class SimulateLoad extends Command
{
    protected $signature = 'simulate:load {--jobs=1000} {--duration=1}';
    protected $description = 'Simulate heavy load on queue';

    public function handle(): int
    {
        $jobs = (int) $this->option('jobs');
        $duration = (int) $this->option('duration');

        $this->info("Dispatching {$jobs} jobs (duration: {$duration}s)...");

        for ($i = 0; $i < $jobs; $i++) {
            SimulateHeavyJob::dispatch($duration)->onQueue('high');
        }

        $this->info("Done.");

        return Command::SUCCESS;
    }
}
