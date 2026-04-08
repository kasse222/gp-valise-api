<?php

namespace App\Actions\Payment;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Throwable;

class HandlePaymentWebhook
{
    public function execute(array $payload): void
    {
        DB::transaction(function () use ($payload) {
            $eventId = $payload['event_id'] ?? null;
            $event = $payload['event'] ?? null;
            $providerId = $payload['provider_transaction_id'] ?? null;

            if (! $eventId || ! $event || ! $providerId) {
                return;
            }

            $existingLog = WebhookLog::query()
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($existingLog) {
                return;
            }

            $log = WebhookLog::query()->create([
                'event_id' => $eventId,
                'event' => $event,
                'provider_transaction_id' => $providerId,
                'status' => WebhookLog::STATUS_RECEIVED,
                'payload' => $payload,
            ]);

            try {
                $transaction = Transaction::query()
                    ->where('provider_transaction_id', $providerId)
                    ->lockForUpdate()
                    ->first();

                if (! $transaction || $transaction->type !== TransactionTypeEnum::REFUND) {
                    $this->markIgnored($log);
                    return;
                }

                if ($transaction->isSucceeded() || $transaction->isFailed()) {
                    $this->markIgnored($log);
                    return;
                }

                match ($event) {
                    'refund.completed' => $this->handleSuccess($transaction, $transaction->booking, $log),
                    'refund.failed' => $this->handleFailure($transaction, $log),
                    default => $this->markIgnored($log),
                };
            } catch (Throwable $e) {
                $log->update([
                    'status' => WebhookLog::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'processed_at' => now(),
                ]);

                throw $e;
            }
        });
    }

    private function handleSuccess(Transaction $transaction, ?Booking $booking, WebhookLog $log): void
    {
        $transaction->update([
            'status' => TransactionStatusEnum::COMPLETED,
            'processed_at' => now(),
        ]);

        if ($booking) {
            $booking->transitionTo(
                BookingStatusEnum::REMBOURSEE,
                null,
                'Refund confirmé par webhook'
            );
        }

        $log->update([
            'status' => WebhookLog::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    private function handleFailure(Transaction $transaction, WebhookLog $log): void
    {
        $transaction->update([
            'status' => TransactionStatusEnum::FAILED,
            'processed_at' => now(),
        ]);

        $log->update([
            'status' => WebhookLog::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    private function markIgnored(WebhookLog $log): void
    {
        $log->update([
            'status' => WebhookLog::STATUS_IGNORED,
            'processed_at' => now(),
        ]);
    }
}
