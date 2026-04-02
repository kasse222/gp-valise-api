<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Events\BookingConfirmed;

class ConfirmBooking
{
    public function execute(int $bookingId, User $user): Booking
    {
        $booking = DB::transaction(function () use ($bookingId, $user) {
            $booking = Booking::query()
                ->with(['trip', 'bookingItems'])
                ->lockForUpdate()
                ->findOrFail($bookingId);

            $trip = $booking->trip()
                ->lockForUpdate()
                ->firstOrFail();

            if (! $booking->canBeUpdatedTo(BookingStatusEnum::CONFIRMEE, $user)) {
                throw ValidationException::withMessages([
                    'booking' => 'Confirmation non autorisée ou transition invalide.',
                ]);
            }

            $thisBookingKg = (float) $booking->bookingItems->sum('kg_reserved');

            $occupiedKg = $trip->kgReserved();

            if (
                $booking->status === BookingStatusEnum::EN_PAIEMENT
                && $booking->payment_expires_at !== null
                && $booking->payment_expires_at->isFuture()
            ) {
                $occupiedKg -= $thisBookingKg;
            }

            if (($occupiedKg + $thisBookingKg) > $trip->capacity) {
                throw ValidationException::withMessages([
                    'trip' => 'Capacité du trajet insuffisante pour confirmer cette réservation.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::CONFIRMEE,
                $user,
                'Confirmation par le voyageur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip']);
        });

        // 🔥 IMPORTANT : dispatch après commit
        event(new BookingConfirmed($booking));

        return $booking;
    }
}
