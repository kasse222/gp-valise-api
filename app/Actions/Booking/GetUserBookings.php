<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;

class GetUserBookings
{
    /**
     * Récupère les réservations visibles par un utilisateur
     * - Si VOYAGEUR : celles de ses trajets
     * - Sinon : ses propres réservations
     */
    public static function execute(User $user)
    {
        $relations = [
            'bookingItems.luggage',
            'trip.user',
            'statusHistories', // ✅ relation correcte
        ];

        // Cas VOYAGEUR → réservations liées à SES trajets
        if ($user->isVoyageur()) {
            return Booking::with($relations)
                ->whereHas('trip', fn($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get();
        }

        // Cas expéditeur/admin → ses propres réservations
        return Booking::with($relations)
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }
}
