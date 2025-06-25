<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;

class GetUserBookings
{
    public static function execute(User $user)
    {
        // Si c’est un VOYAGEUR → récupérer les réservations de SES trajets
        if ($user->isVoyageur()) {
            return Booking::with([
                'bookingItems.luggage',
                'trip.user',
                'status_History',
            ])
                ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get();
        }

        // Sinon (expéditeur ou autre) → ses propres réservations
        return Booking::with([
            'bookingItems.luggage',
            'trip.user',
            'status_History',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }
}
