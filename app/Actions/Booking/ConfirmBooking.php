<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConfirmBooking
{
    /**
     * Confirme une réservation si les règles métier sont respectées.
     *
     * @throws ValidationException
     */
    public function execute(int $bookingId): Booking
    {
        $booking = Booking::with(['trip', 'bookingItems'])->findOrFail($bookingId);

        // 🔐 1. Booking doit être en attente
        if ($booking->status !== 'en_attente') {
            throw ValidationException::withMessages([
                'status' => 'La réservation doit être en attente pour être confirmée.',
            ]);
        }

        // 🔐 2. L'utilisateur doit être le voyageur (propriétaire du trip)
        $user = Auth::user();
        if ($booking->trip->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'user' => 'Vous n\'êtes pas autorisé à confirmer cette réservation.',
            ]);
        }

        // 📦 3. Calculer capacité restante sur le trip
        $totalKgReserved = $booking->trip->bookings()
            ->where('status', 'confirmee')
            ->with('bookingItems')
            ->get()
            ->flatMap->bookingItems
            ->sum('kg_reserved');

        $tripCapacity = $booking->trip->capacity;
        $thisBookingKg = $booking->bookingItems->sum('kg_reserved');

        if (($totalKgReserved + $thisBookingKg) > $tripCapacity) {
            throw ValidationException::withMessages([
                'trip' => 'La capacité du trajet est insuffisante pour confirmer cette réservation.',
            ]);
        }

        // ✅ 4. Confirmation
        $booking->update(['status' => 'confirmee']);

        return $booking->fresh(['bookingItems.luggage', 'trip']);
    }
}
