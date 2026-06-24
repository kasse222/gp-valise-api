<?php

declare(strict_types=1);

namespace App\Actions\Traveler;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

final class ComputeTravelerEarnings
{
    /**
     * @return array<string, array{escrow:int, pending:int, paid:int}>
     */
    public function execute(User $traveler): array
    {
        $buckets = [];

        // Escrow : charges COMPLETED sur bookings CONFIRMEE|EN_TRANSIT des trajets du GP
        $escrowCharges = Transaction::query()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->whereHas('booking', function ($q) use ($traveler): void {
                $q->whereIn('status', [
                    BookingStatusEnum::CONFIRMEE,
                    BookingStatusEnum::EN_TRANSIT,
                ])->whereHas('trip', function ($t) use ($traveler): void {
                    $t->where('user_id', $traveler->id);
                });
            })
            ->get(['amount', 'currency']);

        $this->accumulate($buckets, $escrowCharges, 'escrow');

        // Payouts du GP (user_id = GP garanti par CreatePayoutTransaction)
        $payouts = $traveler->transactions()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->get(['amount', 'currency', 'status']);

        $this->accumulate($buckets, $payouts->whereIn('status', [
            TransactionStatusEnum::PENDING,
            TransactionStatusEnum::PROCESSING,
        ]), 'pending');

        $this->accumulate($buckets, $payouts->where('status', TransactionStatusEnum::COMPLETED), 'paid');

        return $buckets;
    }

    /** @param Collection<int, Transaction> $transactions */
    private function accumulate(array &$buckets, Collection $transactions, string $key): void
    {
        foreach ($transactions as $tx) {
            $code = $tx->currency->value;
            if (! isset($buckets[$code])) {
                $buckets[$code] = ['escrow' => 0, 'pending' => 0, 'paid' => 0];
            }
            $buckets[$code][$key] += (int) $tx->amount;
        }
    }
}
