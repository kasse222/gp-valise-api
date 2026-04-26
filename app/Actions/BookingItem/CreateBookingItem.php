<?php

namespace App\Actions\BookingItem;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Validators\BookingItemValidator;

class CreateBookingItem
{
    public static function execute(Booking $booking, array $data): BookingItem
    {

        app(BookingItemValidator::class)->validateCreate($booking, $data);

        $data['booking_id'] = $booking->id;
        $data['trip_id'] = $booking->trip_id;


        return BookingItem::create($data);
    }
}
