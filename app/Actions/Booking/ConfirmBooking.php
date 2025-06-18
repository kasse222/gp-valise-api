<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Status\BookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConfirmBooking
{
    public function execute(int $bookingId): Booking
    {
        $user = Auth::user();
        $booking = Booking::with(['trip', 'bookingItems'])->findOrFail($bookingId);

        // 🔐 Vérifie l'autorisation métier
        if (! $booking->canBeUpdatedTo(BookingStatus::CONFIRMEE, $user)) {
            throw ValidationException::withMessages([
                'booking' => 'Confirmation non autorisée ou transition invalide.',
            ]);
        }

        // 📦 Vérifie la capacité du trajet
        $totalKgReserved = $booking->trip->bookings()
            ->where('status', BookingStatus::CONFIRMEE)
            ->with('bookingItems')
            ->get()
            ->flatMap->bookingItems
            ->sum('kg_reserved');

        $tripCapacity = $booking->trip->capacity;
        $thisBookingKg = $booking->bookingItems->sum('kg_reserved');

        if (($totalKgReserved + $thisBookingKg) > $tripCapacity) {
            throw ValidationException::withMessages([
                'trip' => 'Capacité du trajet insuffisante pour confirmer cette réservation.',
            ]);
        }

        // ✅ Transition vers 'confirmée'
        $success = $booking->transitionTo(BookingStatus::CONFIRMEE, $user, 'Confirmation par le voyageur');

        if (! $success) {
            throw ValidationException::withMessages([
                'booking' => 'Impossible de confirmer cette réservation.',
            ]);
        }

        return $booking->fresh(['bookingItems.luggage', 'trip']);
    }
}
