<?php

namespace App\Actions\Transaction;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\TransactionEligibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePayoutTransaction
{
    private const COMMISSION_RATE = 0.15;

    public function __construct(
        private readonly TransactionEligibilityService $eligibility,
    ) {}

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

            if (! $this->eligibility->canCreatePayout($booking)) {
                throw ValidationException::withMessages([
                    'booking' => 'Ce booking ne peut pas déclencher de payout.',
                ]);
            }

            $charge = $this->eligibility->completedCharge($booking);

            if (! $charge) {
                throw ValidationException::withMessages([
                    'booking' => 'Aucune charge complétée disponible pour ce payout.',
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
