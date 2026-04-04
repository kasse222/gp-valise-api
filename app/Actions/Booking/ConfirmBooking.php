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
    public function execute(Booking $booking, User $actor): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor) {
            $booking = Booking::query()
                ->with(['trip', 'bookingItems.luggage', 'transaction'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $trip = $booking->trip()
                ->lockForUpdate()
                ->firstOrFail();

            if (! $booking->canBeUpdatedTo(BookingStatusEnum::CONFIRMEE, $actor)) {
                throw ValidationException::withMessages([
                    'booking' => 'Confirmation non autorisée ou transition invalide.',
                ]);
            }

            if (! $booking->hasSuccessfulChargeTransaction()) {
                throw ValidationException::withMessages([
                    'booking' => 'Le booking ne peut pas être confirmé sans paiement validé.',
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
                    'trip' => 'Capacité du trajet insuffisante.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::CONFIRMEE,
                $actor,
                'Confirmation par le voyageur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip', 'transaction']);
        });

        event(new BookingConfirmed($booking));

        return $booking;
    }
}
