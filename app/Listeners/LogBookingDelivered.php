<?php

namespace App\Listeners;

use App\Events\BookingDelivered;
use Illuminate\Support\Facades\Log;

class LogBookingDelivered
{
    public function handle(BookingDelivered $event): void
    {
        Log::info('booking.delivered', [
            'booking_id' => $event->booking->id,
            'user_id' => $event->booking->user_id,
            'trip_id' => $event->booking->trip_id,
            'status' => $event->booking->status?->value,
        ]);
    }
}
