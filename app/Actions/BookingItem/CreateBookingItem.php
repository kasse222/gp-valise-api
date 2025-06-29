<?php

namespace App\Actions\BookingItem;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Validators\BookingItemValidator;

class CreateBookingItem
{
    public static function execute(Booking $booking, array $data): BookingItem
    {
        // 1. Vérifications métier
        app(BookingItemValidator::class)->validate($booking, $data);

        // 2. Création de l’item lié à la réservation
        return $booking->bookingItems()->create($data);
    }
}
