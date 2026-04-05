<?php

namespace App\Actions\Transaction;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePayoutTransaction
{
    public function execute(Booking $booking): Transaction
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->with('trip')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if (! $booking->canTriggerPayout()) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de payout.',
                ]);
            }

            $charge = $booking->transactions()
                ->where('type', TransactionTypeEnum::CHARGE)
                ->where('status', TransactionStatusEnum::COMPLETED)
                ->latest()
                ->first();

            if (! $charge) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucune transaction de charge complétée trouvée.',
                ]);
            }

            return Transaction::query()->create([
                'user_id' => $booking->trip->user_id,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::PAYOUT,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'method' => $charge->method,
                'status' => TransactionStatusEnum::PENDING,
                'processed_at' => null,
            ]);
        });
    }
}
