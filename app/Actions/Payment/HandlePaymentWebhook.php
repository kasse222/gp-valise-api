<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WebhookLogStatusEnum;
use App\Exceptions\RetryableWebhookException;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Throwable;

class HandlePaymentWebhook
{
    public function execute(array $payload, ?string $correlationId = null): void
    {
        DB::transaction(function () use ($payload, $correlationId) {
            $eventId    = $payload['event_id'] ?? null;
            $eventType  = $payload['event_type'] ?? null;
            $providerId = $payload['provider_transaction_id'] ?? null;

            if (! $eventId || ! $eventType || ! $providerId) {
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
                'event_id'                => $eventId,
                'correlation_id'          => $correlationId,
                'event'                   => $eventType,
                'provider_transaction_id' => $providerId,
                'status'                  => WebhookLogStatusEnum::RECEIVED,
                'payload'                 => $payload,
            ]);

            try {
                $transaction = Transaction::query()
                    ->where('provider_transaction_id', $providerId)
                    ->lockForUpdate()
                    ->first();

                if (! $transaction) {
                    throw new RetryableWebhookException(
                        "Transaction introuvable pour provider_transaction_id={$providerId}"
                    );
                }

                if ($transaction->isSucceeded() || $transaction->isFailed()) {
                    $this->markIgnored($log, 'Transaction déjà finalisée');
                    return;
                }

                match ($eventType) {
                    'transaction.success'
                    => $this->handleChargeSuccess($transaction, $transaction->booking, $log),
                    'transaction.failed'
                    => $this->handleChargeFailure($transaction, $log),
                    'refund.completed'
                    => $this->handleRefundSuccess($transaction, $transaction->booking, $log),
                    'refund.failed'
                    => $this->handleRefundFailure($transaction, $log),
                    default
                    => $this->markIgnored($log, "Event non supporté: {$eventType}"),
                };
            } catch (RetryableWebhookException $e) {
                $log->update([
                    'status'        => WebhookLogStatusEnum::FAILED,
                    'error_message' => $e->getMessage(),
                    'processed_at'  => now(),
                ]);
                throw $e;
            } catch (Throwable $e) {
                $log->update([
                    'status'        => WebhookLogStatusEnum::FAILED,
                    'error_message' => $e->getMessage(),
                    'processed_at'  => now(),
                ]);
                throw $e;
            }
        });
    }

    private function handleChargeSuccess(Transaction $transaction, ?Booking $booking, WebhookLog $log): void
    {
        if ($transaction->type !== TransactionTypeEnum::CHARGE) {
            $this->markIgnored($log, 'Event transaction.success sur une transaction non-CHARGE');
            return;
        }

        $transaction->update([
            'status'       => TransactionStatusEnum::COMPLETED,
            'processed_at' => now(),
        ]);

        if ($booking) {
            $booking->transitionTo(
                BookingStatusEnum::CONFIRMEE,
                null,
                'Paiement confirmé par webhook'
            );
        }

        $log->update([
            'status'       => WebhookLogStatusEnum::PROCESSED,
            'processed_at' => now(),
        ]);
    }

    private function handleChargeFailure(Transaction $transaction, WebhookLog $log): void
    {
        if ($transaction->type !== TransactionTypeEnum::CHARGE) {
            $this->markIgnored($log, 'Event transaction.failed sur une transaction non-CHARGE');
            return;
        }

        $transaction->update([
            'status'       => TransactionStatusEnum::FAILED,
            'processed_at' => now(),
        ]);

        $log->update([
            'status'       => WebhookLogStatusEnum::PROCESSED,
            'processed_at' => now(),
        ]);
    }

    private function handleRefundSuccess(Transaction $transaction, ?Booking $booking, WebhookLog $log): void
    {
        if ($transaction->type !== TransactionTypeEnum::REFUND) {
            $this->markIgnored($log, 'Event refund.completed sur une transaction non-REFUND');
            return;
        }

        $transaction->update([
            'status'       => TransactionStatusEnum::COMPLETED,
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
            'status'       => WebhookLogStatusEnum::PROCESSED,
            'processed_at' => now(),
        ]);
    }

    private function handleRefundFailure(Transaction $transaction, WebhookLog $log): void
    {
        if ($transaction->type !== TransactionTypeEnum::REFUND) {
            $this->markIgnored($log, 'Event refund.failed sur une transaction non-REFUND');
            return;
        }

        $transaction->update([
            'status'       => TransactionStatusEnum::FAILED,
            'processed_at' => now(),
        ]);

        $log->update([
            'status'       => WebhookLogStatusEnum::PROCESSED,
            'processed_at' => now(),
        ]);
    }

    private function markIgnored(WebhookLog $log, ?string $reason = null): void
    {
        $log->update([
            'status'        => WebhookLogStatusEnum::IGNORED,
            'error_message' => $reason,
            'processed_at'  => now(),
        ]);
    }
}
