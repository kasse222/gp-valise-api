<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use Illuminate\Support\Facades\Log;

class LogBookingConfirmed
{
    public function handle(BookingConfirmed $event): void
    {
        Log::info('booking.confirmed', [
            'booking_id' => $event->booking->id,
            'user_id' => $event->booking->user_id,
            'trip_id' => $event->booking->trip_id,
            'status' => $event->booking->status?->value,
            'confirmed_at' => $event->booking->confirmed_at,
        ]);
    }
}
