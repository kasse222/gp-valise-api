<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetBookingDetails
{
    public function execute(int|string $bookingId): Booking
    {
        return Booking::with([
            'bookingItems.luggage',
            'trip.user',
            'statusHistories',
            'transaction',
        ])->findOrFail($bookingId);
    }
}
