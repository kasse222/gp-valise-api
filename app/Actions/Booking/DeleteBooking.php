<?php

namespace App\Actions\Booking;

use App\Models\Booking;

class DeleteBooking
{
    public static function execute(Booking $booking): void
    {
        foreach ($booking->bookingItems as $item) {
            $item->luggage->update(['status' => 'en_attente']);
            $item->delete();
        }

        $booking->delete();
    }
}
