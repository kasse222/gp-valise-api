<?php

namespace App\Actions\Transaction;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundTransaction
{
    public function execute(Transaction $charge, ?string $reason = null): Transaction
    {
        $refund = DB::transaction(function () use ($charge) {
            $charge = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($charge->id);

            if (! $charge->isCharge() || ! $charge->isSucceeded()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Seule une charge complétée peut être remboursée.',
                ]);
            }

            $booking = Booking::query()
                ->lockForUpdate()
                ->find($charge->booking_id);

            if (! $booking) {
                throw ValidationException::withMessages([
                    'booking' => 'Transaction sans booking.',
                ]);
            }

            $allowedStatuses = [
                BookingStatusEnum::ANNULE,
                BookingStatusEnum::EXPIREE,
                BookingStatusEnum::PAIEMENT_ECHOUE,
                BookingStatusEnum::EN_LITIGE,
            ];

            if (! in_array($booking->status, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de remboursement.',
                ]);
            }

            $bookingTransactions = Transaction::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            $hasPayout = $bookingTransactions->contains(
                fn(Transaction $transaction) => $transaction->type === TransactionTypeEnum::PAYOUT
            );

            if ($hasPayout) {
                throw ValidationException::withMessages([
                    'booking' => 'Impossible de rembourser après création du payout.',
                ]);
            }

            $hasRefund = $bookingTransactions->contains(
                fn(Transaction $transaction) => $transaction->type === TransactionTypeEnum::REFUND
            );

            if ($hasRefund) {
                throw ValidationException::withMessages([
                    'booking' => 'Un remboursement existe déjà pour ce booking.',
                ]);
            }

            $hasSuccessfulCharge = $bookingTransactions->contains(
                fn(Transaction $transaction) =>
                $transaction->type === TransactionTypeEnum::CHARGE
                    && $transaction->status === TransactionStatusEnum::COMPLETED
            );

            if (! $hasSuccessfulCharge) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucune charge complétée ne permet ce remboursement.',
                ]);
            }

            return Transaction::query()->create([
                'user_id' => $charge->user_id,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::REFUND,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'method' => $charge->method,
                'status' => TransactionStatusEnum::COMPLETED,
                'processed_at' => now(),
            ]);
        });

        event(new TransactionRefunded($refund, $reason));

        return $refund;
    }
}
