<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Models\Booking;

class GetBookingDetails
{
    public function execute(int|string $bookingId): Booking
    {
        return Booking::query()
            ->with([
                'bookingItems.luggage',
                'trip.user',
                'statusHistories',
                'transactions',
                'dispute',
            ])
            ->findOrFail($bookingId);
    }
}
