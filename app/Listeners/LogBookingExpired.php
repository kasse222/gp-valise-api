<?php

namespace App\Listeners;

use App\Events\BookingExpired;
use Illuminate\Support\Facades\Log;

class LogBookingExpired
{
    public function handle(BookingExpired $event): void
    {
        Log::info('Booking.expired', [
            'booking_id' => $event->booking->id,
            'user_id' => $event->booking->user_id,
            'trip_id' => $event->booking->trip_id,
            'status' => $event->booking->status?->value,
            'expired_at' => $event->booking->expired_at?->toISOString(),
        ]);
    }
}
