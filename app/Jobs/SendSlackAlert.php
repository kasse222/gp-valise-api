<?php

namespace App\Jobs;

use App\Services\Alerting\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSlackAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        public string $message,
        public array $context = [],
        public string $level = 'info',
    ) {
        $this->onQueue('low');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SlackNotifier $notifier): void
    {
        $notifier->send(
            $this->message,
            $this->context,
            $this->level
        );
    }
}
