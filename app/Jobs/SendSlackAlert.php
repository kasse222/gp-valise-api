<?php

namespace App\Jobs;

use App\Services\Alerting\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSlackAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 10;

    public function __construct(
        public string $message,
        public array $context = [],
        public string $level = 'info',
    ) {
        // 👉 CRUCIAL : jamais sur high
        $this->onQueue('low');
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
