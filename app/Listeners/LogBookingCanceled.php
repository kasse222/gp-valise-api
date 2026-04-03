<?php

namespace App\Listeners;

use App\Events\BookingCanceled;
use Illuminate\Support\Facades\Log;

class LogBookingCanceled
{
    public function handle(BookingCanceled $event): void
    {
        $booking = $event->booking;

        Log::info('Booking canceled', [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'trip_id' => $booking->trip_id,
            'status' => $booking->status?->value,
            'cancelled_at' => $booking->cancelled_at,
        ]);
    }
}
