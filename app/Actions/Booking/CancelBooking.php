<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Status\BookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function execute(int $bookingId): Booking
    {
        $user = Auth::user();
        $booking = Booking::with(['bookingItems.luggage', 'trip'])->findOrFail($bookingId);

        // 🔐 Autorisation métier via Booking::canBeUpdatedTo
        if (! $booking->canBeUpdatedTo(BookingStatus::ANNULE, $user)) {
            throw ValidationException::withMessages([
                'booking' => 'Annulation non autorisée ou statut invalide.',
            ]);
        }

        // ✅ Transition métier centralisée
        $success = $booking->transitionTo(BookingStatus::ANNULE, $user, 'Annulation par l’utilisateur');

        if (! $success) {
            throw ValidationException::withMessages([
                'booking' => 'Impossible d’annuler la réservation.',
            ]);
        }

        return $booking->fresh(['bookingItems.luggage']);
    }
}
