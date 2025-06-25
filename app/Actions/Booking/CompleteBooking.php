<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompleteBooking
{
    /**
     * Marquer une réservation comme livrée.
     */
    public function execute(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            // Vérification du statut actuel
            if (! $booking->status->canTransitionTo(BookingStatusEnum::LIVREE)) {
                abort(400, 'La réservation ne peut pas être marquée comme livrée.');
            }

            // Mise à jour du statut
            $booking->update(['status' => BookingStatusEnum::LIVREE]);

            // Historisation
            $booking->statusHistories()->create([
                'old_status' => $booking->status,
                'new_status' => BookingStatusEnum::LIVREE,
                'user_id'    => Auth::id(),
                'reason'     => 'Réservation livrée par le voyageur.',
            ]);

            return $booking->fresh(['bookingItems.luggage', 'trip']);
        });
    }
}
