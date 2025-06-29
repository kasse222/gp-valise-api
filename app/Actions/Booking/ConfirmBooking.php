<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
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
        if (! $booking->canBeUpdatedTo(BookingStatusEnum::CONFIRMEE, $user)) {
            throw ValidationException::withMessages([
                'booking' => 'Confirmation non autorisée ou transition invalide.',
            ]);
        }

        // 📦 Vérifie la capacité du trajet
        $totalKgReserved = $booking->trip->bookings()
            ->where('status', BookingStatusEnum::CONFIRMEE)
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
        $booking->transitionTo(BookingStatusEnum::CONFIRMEE, $user, 'Confirmation par le voyageur');


        return $booking->fresh(['bookingItems.luggage', 'trip']);
    }
}
