<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Jobs\ReleaseEscrowPayoutJob;
use App\Models\Booking;

final class ReleaseEscrowBatch
{
    public function execute(): int
    {
        $bookings = Booking::query()
            ->where('status', BookingStatusEnum::LIVREE->value)
            ->whereNotNull('escrow_releasable_at')
            ->where('escrow_releasable_at', '<=', now())
            ->whereNull('disputed_at')
            // Exclut tout booking ayant déjà un PAYOUT, REFUND ou FEE —
            // doit refléter exactement les conditions de
            // TransactionEligibilityService::canCreatePayout(), sinon
            // le même booking est resélectionné à chaque run et spam
            // les logs critiques en boucle (cf. incident ReleaseEscrowPayoutJob).
            ->whereDoesntHave('transactions', function ($q) {
                $q->whereIn('type', [
                    TransactionTypeEnum::PAYOUT->value,
                    TransactionTypeEnum::REFUND->value,
                    TransactionTypeEnum::FEE->value,
                ]);
            })
            ->get();

        foreach ($bookings as $booking) {
            ReleaseEscrowPayoutJob::dispatch($booking);
        }

        return $bookings->count();
    }
}
