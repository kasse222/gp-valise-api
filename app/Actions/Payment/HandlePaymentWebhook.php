<?php

namespace App\Actions\Payment;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class HandlePaymentWebhook
{
    public function execute(array $payload): void
    {
        DB::transaction(function () use ($payload) {

            $event = $payload['event'] ?? null;
            $providerId = $payload['provider_transaction_id'] ?? null;

            if (! $event || ! $providerId) {
                return; // MVP : on ignore silencieusement
            }

            $transaction = Transaction::query()
                ->where('provider_transaction_id', $providerId)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return; // id inconnu → ignore
            }

            // 🔒 Idempotence
            if ($transaction->status->isFinal()) {
                return;
            }

            if ($transaction->type !== TransactionTypeEnum::REFUND) {
                return; // MVP : on ne gère que refund
            }

            $booking = $transaction->booking;

            match ($event) {
                'refund.completed' => $this->handleSuccess($transaction, $booking),
                'refund.failed'    => $this->handleFailure($transaction),
                default            => null,
            };
        });
    }

    private function handleSuccess(Transaction $transaction, ?Booking $booking): void
    {
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
    }

    private function handleFailure(Transaction $transaction): void
    {
        $transaction->update([
            'status'       => TransactionStatusEnum::FAILED,
            'processed_at' => now(),
        ]);
    }
}
