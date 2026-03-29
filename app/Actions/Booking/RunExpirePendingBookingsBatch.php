<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use Throwable;

class RunExpirePendingBookingsBatch
{
    public function __construct(
        private readonly ExpirePendingBooking $action
    ) {}

    public function execute(int $chunkSize = 100): array
    {
        $chunkSize = max(1, $chunkSize);

        $scanned = 0;
        $expired = 0;
        $skipped = 0;
        $failed = 0;

        Booking::query()
            ->where('status', BookingStatusEnum::EN_PAIEMENT->value)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById($chunkSize, function ($bookings) use (&$scanned, &$expired, &$skipped, &$failed) {
                foreach ($bookings as $booking) {
                    $scanned++;

                    try {
                        if (! $booking->isPaymentExpired()) {
                            $skipped++;
                            continue;
                        }

                        $this->action->execute($booking);
                        $expired++;
                    } catch (Throwable $e) {
                        $failed++;

                        report($e);

                        logger()->warning('RunExpirePendingBookingsBatch failed', [
                            'booking_id' => $booking->id,
                            'status' => $booking->status?->value,
                            'payment_expires_at' => $booking->payment_expires_at,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return compact('scanned', 'expired', 'skipped', 'failed');
    }
}
