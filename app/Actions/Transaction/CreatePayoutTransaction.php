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
    private const COMMISSION_RATE = 0.15;

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

            $existingFee = $booking->transactions()
                ->where('type', TransactionTypeEnum::FEE)
                ->exists();

            if ($existingFee) {
                throw ValidationException::withMessages([
                    'booking' => 'Une commission existe déjà pour ce booking.',
                ]);
            }

            $chargeAmount = (float) $charge->amount;
            $feeAmount = round($chargeAmount * self::COMMISSION_RATE, 2);
            $payoutAmount = round($chargeAmount - $feeAmount, 2);

            Transaction::query()->create([
                'user_id' => $booking->trip->user_id,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::FEE,
                'amount' => $feeAmount,
                'currency' => $charge->currency,
                'method' => $charge->method,
                'status' => TransactionStatusEnum::COMPLETED,
                'processed_at' => now(),
            ]);

            return Transaction::query()->create([
                'user_id' => $booking->trip->user_id,
                'booking_id' => $booking->id,
                'type' => TransactionTypeEnum::PAYOUT,
                'amount' => $payoutAmount,
                'currency' => $charge->currency,
                'method' => $charge->method,
                'status' => TransactionStatusEnum::PENDING,
                'processed_at' => null,
            ]);
        });
    }
}
