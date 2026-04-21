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
        app(BookingItemValidator::class)->validateCreate($booking, $data);
        // 2. Ajouts explicites
        $data['booking_id'] = $booking->id;
        $data['trip_id'] = $booking->trip_id; // 💡 fix critique ici

        // 3. Création de l’item
        return BookingItem::create($data);
    }
}
