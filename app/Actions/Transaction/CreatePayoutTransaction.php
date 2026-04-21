<?php

namespace App\Actions\Transaction;

use App\Enums\BookingStatusEnum;
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

            Transaction::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();

            if ($booking->status !== BookingStatusEnum::LIVREE) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de payout.',
                ]);
            }

            $charge = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::CHARGE)
                ->where('status', TransactionStatusEnum::COMPLETED)
                ->latest()
                ->first();

            if (! $charge) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de payout.',
                ]);
            }

            $hasPayout = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::PAYOUT)
                ->exists();

            if ($hasPayout) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de payout.',
                ]);
            }

            $hasFee = Transaction::query()
                ->where('booking_id', $booking->id)
                ->where('type', TransactionTypeEnum::FEE)
                ->exists();

            if ($hasFee) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de payout.',
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
