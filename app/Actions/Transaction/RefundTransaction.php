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
        $result = DB::transaction(function () use ($charge, $reason) {
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

            // Verrouille aussi les transactions du booking pour éviter les doubles refunds concurrents
            Transaction::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            if (! $booking->canTriggerRefund()) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de remboursement.',
                ]);
            }

            $refundAmount = $booking->refundableAmount();

            if ($refundAmount <= 0) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucun montant remboursable disponible pour ce booking.',
                ]);
            }

            $refund = Transaction::query()->create([
                'user_id'      => $charge->user_id,
                'booking_id'   => $booking->id,
                'type'         => TransactionTypeEnum::REFUND,
                'amount'       => $refundAmount,
                'currency'     => $charge->currency,
                'method'       => $charge->method,
                'status'       => TransactionStatusEnum::COMPLETED,
                'processed_at' => now(),
            ]);

            $booking->transitionTo(
                BookingStatusEnum::REMBOURSEE,
                null,
                $reason ?? 'Remboursement manuel après litige'
            );

            return $refund;
        });

        event(new TransactionRefunded($result, $reason));

        return $result;
    }
}
