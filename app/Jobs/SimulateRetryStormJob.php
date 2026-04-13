<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SimulateRetryStormJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 10;

    public function __construct(
        public int $duration = 1,
    ) {
        $this->onQueue('high');
    }

    public function backoff(): array
    {
        return [5, 10, 20, 30];
    }

    public function handle(): void
    {
        sleep($this->duration);

        throw new RuntimeException('Simulated retry storm failure.');
    }
}
