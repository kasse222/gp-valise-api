<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Jobs\ReleaseEscrowPayoutJob;
use App\Models\Booking;

final class ReleaseEscrowBatch
{
    public function execute(): int
    {
        $bookings = Booking::query()
            ->where('status', \App\Enums\BookingStatusEnum::LIVREE->value)
            ->whereNotNull('escrow_releasable_at')
            ->where('escrow_releasable_at', '<=', now())
            ->whereNull('disputed_at')
            ->whereDoesntHave('transactions', function ($q) {
                $q->whereIn('type', [
                    \App\Enums\TransactionTypeEnum::PAYOUT->value,
                    \App\Enums\TransactionTypeEnum::REFUND->value,
                ]);
            })
            ->get();

        foreach ($bookings as $booking) {
            ReleaseEscrowPayoutJob::dispatch($booking);
        }

        return $bookings->count();
    }
}
