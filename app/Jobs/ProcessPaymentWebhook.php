<?php

namespace App\Jobs;

use App\Actions\Payment\HandlePaymentWebhook;
use App\Exceptions\RetryableWebhookException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        Log::channel('stack')->critical('WEBHOOK DEFINITIVEMENT ECHOUE', [
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
            'job' => static::class,
        ]);

        $alertEmail = config('payment.webhook.alert_email');

        if ($alertEmail) {
            Mail::raw(
                'Webhook failed: ' . json_encode([
                    'payload' => $this->payload,
                    'error' => $exception->getMessage(),
                    'job' => static::class,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                function ($message) use ($alertEmail) {
                    $message->to($alertEmail)
                        ->subject('Webhook Failed 🚨');
                }
            );
        }
    }
}
