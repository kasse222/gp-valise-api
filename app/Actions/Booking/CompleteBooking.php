<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteBooking
{
    /**
     * Marquer une réservation comme livrée par le voyageur.
     */
    public function execute(Booking $booking): Booking
    {
        $user = Auth::user();

        // 🔐 Vérifie les droits métier
        if (! $booking->canBeUpdatedTo(BookingStatusEnum::LIVREE, $user)) {
            throw ValidationException::withMessages([
                'booking' => 'Livraison non autorisée ou statut invalide.',
            ]);
        }

        return DB::transaction(function () use ($booking, $user) {
            // ✅ Transition métier avec timestamp et historique
            $booking->transitionTo(BookingStatusEnum::LIVREE, $user, 'Livraison confirmée par le voyageur');

            return $booking->fresh(['bookingItems.luggage', 'trip']);
        });
    }
}
