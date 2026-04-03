<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;

class GetUserBookings
{
    public function execute(User $user)
    {
        $relations = [
            'bookingItems.luggage',
            'trip.user',
            'statusHistories',
        ];

        if ($user->isVoyageur()) {
            return Booking::with($relations)
                ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get();
        }

        return Booking::with($relations)
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }
}
