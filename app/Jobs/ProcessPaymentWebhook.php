<?php

namespace App\Jobs;

use App\Actions\Payment\HandlePaymentWebhook;
use App\Exceptions\RetryableWebhookException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 30;

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function __construct(
        public array $payload,
    ) {}

    public function handle(HandlePaymentWebhook $action): void
    {
        try {
            $action->execute($this->payload);
        } catch (RetryableWebhookException $e) {
            // ✅ on retry seulement pendant les premières tentatives
            if ($this->attempts() < 3) {
                throw $e;
            }

            // ❗ après plusieurs tentatives, on arrête de rethrow
            Log::warning('Webhook abandonné après retries sur transaction introuvable', [
                'payload' => $this->payload,
                'attempts' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        logger()->error('Webhook définitivement échoué', [
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
        ]);

        //  simulation alerte (MVP)
        \Log::channel('stack')->critical('ALERTE WEBHOOK FAILED', [
            'payload' => $this->payload,
        ]);
    }
}
