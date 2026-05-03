<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Payment\HandlePaymentWebhook;
use App\Exceptions\RetryableWebhookException;
use DateTimeInterface;
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
        public ?string $correlationId = null,
    ) {
        $this->onQueue('high');
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function handle(HandlePaymentWebhook $action): void
    {
        $this->withCorrelationContext();

        try {
            $action->execute($this->payload, $this->correlationId);
        } catch (RetryableWebhookException $exception) {
            if ($this->attempts() < 3) {
                throw $exception;
            }

            Log::warning('Webhook abandonné après plusieurs tentatives retryables', $this->logContext([
                'attempts' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]));
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->withCorrelationContext();

        $context = $this->logContext([
            'error' => $exception->getMessage(),
        ]);

        Log::channel('stack')->critical('WEBHOOK DEFINITIVEMENT ECHOUE', $context);

        dispatch(new SendSlackAlert(
            'Webhook définitivement échoué',
            $context,
            'critical'
        ));
    }

    private function withCorrelationContext(): void
    {
        if ($this->correlationId === null) {
            return;
        }

        Log::withContext([
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function logContext(array $extra = []): array
    {
        return array_merge([
            'payload' => $this->payload,
            'job' => static::class,
            'correlation_id' => $this->correlationId,
        ], $extra);
    }
}
