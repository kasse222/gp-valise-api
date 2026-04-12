<?php

namespace App\Jobs;

use App\Actions\Payment\HandlePaymentWebhook;
use App\Exceptions\RetryableWebhookException;
use App\Services\Alerting\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 30;

    public function __construct(
        public array $payload,
    ) {
        $this->onQueue('high');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function handle(HandlePaymentWebhook $action): void
    {
        try {
            $action->execute($this->payload);
        } catch (RetryableWebhookException $e) {
            if ($this->attempts() < 3) {
                throw $e;
            }

            Log::warning('Webhook abandonné après plusieurs tentatives retryables', [
                'payload' => $this->payload,
                'attempts' => $this->attempts(),
                'error' => $e->getMessage(),
                'job' => static::class,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $context = [
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
            'job' => static::class,
        ];

        Log::channel('stack')->critical('WEBHOOK DEFINITIVEMENT ECHOUE', $context);

        dispatch(new \App\Jobs\SendSlackAlert(
            'Webhook définitivement échoué',
            $context,
            'critical'
        ));
    }
}
