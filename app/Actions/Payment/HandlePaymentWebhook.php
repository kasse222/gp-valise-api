<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WebhookLogStatusEnum;
use App\Events\BookingConfirmed;
use App\Exceptions\RetryableWebhookException;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\WebhookLog;
use App\Services\LedgerWriter;
use Illuminate\Support\Facades\DB;
use Throwable;

class HandlePaymentWebhook
{
    public function __construct(
        private readonly LedgerWriter $ledger,
    ) {}

    public function execute(array $payload, ?string $correlationId = null): void
    {
        $eventId    = $payload['event_id'] ?? null;
        $eventType  = $payload['event_type'] ?? null;
        $providerId = $payload['provider_transaction_id'] ?? null;

        if (! $eventId || ! $eventType || ! $providerId) {
            return;
        }

        // F-011 — ÉTAPE 1 : journaliser la réception HORS de la transaction métier.
        // Si le traitement rollback, le log de réception/échec reste durable.
        $log = DB::transaction(function () use ($eventId, $eventType, $providerId, $correlationId, $payload): ?WebhookLog {
            $existing = WebhookLog::query()
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return null; // doublon — déjà traité
            }

            return WebhookLog::query()->create([
                'event_id'                => $eventId,
                'correlation_id'          => $correlationId,
                'event'                   => $eventType,
                'provider_transaction_id' => $providerId,
                'status'                  => WebhookLogStatusEnum::RECEIVED,
                'payload'                 => $payload,
            ]);
        });

        if ($log === null) {
            return; // idempotence — événement déjà traité
        }

        // F-011 — ÉTAPE 2 : traitement métier dans sa propre transaction.
        // Un rollback ici ne supprime pas le log créé à l'étape 1.
        try {
            DB::transaction(function () use ($payload, $eventId, $eventType, $providerId, $log): void {
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
            });
        } catch (RetryableWebhookException $e) {
            // F-011 — persister l'échec hors rollback
            $this->markFailed($log, $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            $this->markFailed($log, $e->getMessage());
            throw $e;
        }
    }

    private function handleChargeSuccess(
        Transaction $transaction,
        ?Booking $booking,
        WebhookLog $log,
    ): void {
        if ($transaction->type !== TransactionTypeEnum::CHARGE) {
            $this->markIgnored($log, 'Event transaction.success sur une transaction non-CHARGE');
            return;
        }

        $transaction->update([
            'status'       => TransactionStatusEnum::COMPLETED,
            'processed_at' => now(),
        ]);

        $transaction->refresh();
        $this->ledger->writeCharge($transaction);

        if ($booking) {
            $booking->transitionTo(
                BookingStatusEnum::CONFIRMEE,
                null,
                'Paiement confirmé par webhook'
            );
            // Emails de confirmation (sender, traveler, destinataire)
            event(new BookingConfirmed($booking->fresh()));
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

    private function handleRefundSuccess(
        Transaction $transaction,
        ?Booking $booking,
        WebhookLog $log,
    ): void {
        if ($transaction->type !== TransactionTypeEnum::REFUND) {
            $this->markIgnored($log, 'Event refund.completed sur une transaction non-REFUND');
            return;
        }

        $transaction->update([
            'status'       => TransactionStatusEnum::COMPLETED,
            'processed_at' => now(),
        ]);

        $charge = $transaction->booking?->transactions()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->latest()
            ->first();

        if ($charge) {
            $this->ledger->writeRefund($charge, $transaction);
        }

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

    // F-011 — écriture de l'échec dans une transaction courte indépendante
    private function markFailed(WebhookLog $log, string $reason): void
    {
        DB::transaction(function () use ($log, $reason): void {
            $log->update([
                'status'        => WebhookLogStatusEnum::FAILED,
                'error_message' => $reason,
                'processed_at'  => now(),
            ]);
        });
    }
}
