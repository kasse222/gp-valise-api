<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmBooking
{
    public function execute(Booking $booking, User $actor): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor) {
            $booking = Booking::query()
                ->with(['trip', 'bookingItems.luggage'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $trip = $booking->trip()
                ->lockForUpdate()
                ->firstOrFail();

            if ($actor->id !== $trip->user_id) {
                throw ValidationException::withMessages([
                    'booking' => 'Seul le voyageur du trajet peut confirmer cette réservation.',
                ]);
            }

            if (! $booking->status->canTransitionTo(BookingStatusEnum::CONFIRMEE)) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être confirmée depuis son statut actuel.',
                ]);
            }

            if (! $booking->hasSuccessfulChargeTransaction()) {
                throw ValidationException::withMessages([
                    'booking' => 'Le booking ne peut pas être confirmé sans paiement validé.',
                ]);
            }

            $thisBookingGrams = (int) $booking->bookingItems->sum('kg_reserved');
            $occupiedGrams    = $trip->gramsReserved(); // ← kgReserved → gramsReserved

            if (
                in_array($booking->status, [
                    BookingStatusEnum::EN_PAIEMENT,
                    BookingStatusEnum::PENDING_APPROVAL,
                ], true)
                && (
                    $booking->status === BookingStatusEnum::EN_PAIEMENT
                    ? $booking->payment_expires_at?->isFuture()
                    : true
                )
            ) {
                $occupiedGrams -= $thisBookingGrams;
            }

            if (($occupiedGrams + $thisBookingGrams) > $trip->capacity) {
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
