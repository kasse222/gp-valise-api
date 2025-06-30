<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Enums\LuggageStatusEnum;

class DeleteBooking
{
    public static function execute(Booking $booking): void
    {
        foreach ($booking->bookingItems as $item) {
            if ($item->luggage) {
                $item->luggage->update([
                    'status' => LuggageStatusEnum::EN_ATTENTE
                ]);
            }

            $item->delete();
        }

        $booking->delete();
    }
}
