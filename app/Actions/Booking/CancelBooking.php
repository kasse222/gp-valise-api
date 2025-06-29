<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
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
        if (! $booking->canBeUpdatedTo(BookingStatusEnum::ANNULE, $user)) {
            throw ValidationException::withMessages([
                'booking' => 'Annulation non autorisée ou statut invalide.',
            ]);
        }

        // ✅ Transition métier centralisée
        $booking->transitionTo(BookingStatusEnum::ANNULE, $user, 'Annulation par l’utilisateur');


        return $booking->fresh(['bookingItems.luggage']);
    }
}
