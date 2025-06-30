<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetBookingDetails
{
    /**
     * Récupère une réservation avec ses relations clés
     */
    public static function execute(int|string $bookingId): Booking
    {
        return Booking::with([
            'bookingItems.luggage',
            'trip.user',
            'statusHistories',
            'transaction',
        ])
            ->findOrFail($bookingId);
    }
}
