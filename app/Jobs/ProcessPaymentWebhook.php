<?php

namespace App\Jobs;

use App\Actions\Payment\HandlePaymentWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public array $payload,
    ) {}

    public function handle(HandlePaymentWebhook $action): void
    {
        $action->execute($this->payload);
    }
}
