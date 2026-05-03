<?php

declare(strict_types=1);

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

        $result = [
            'scanned' => 0,
            'expired' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        Booking::query()
            ->where('status', BookingStatusEnum::EN_PAIEMENT)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById($chunkSize, function ($bookings) use (&$result) {
                foreach ($bookings as $booking) {
                    $result['scanned']++;

                    try {
                        if (! $booking->isPaymentExpired()) {
                            $result['skipped']++;
                            continue;
                        }

                        $beforeStatus = $booking->status;

                        $booking = $this->action->execute($booking);

                        if ($beforeStatus !== $booking->status) {
                            $result['expired']++;
                            continue;
                        }

                        $result['skipped']++;
                    } catch (Throwable $e) {
                        $result['failed']++;

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

        return $result;
    }
}
