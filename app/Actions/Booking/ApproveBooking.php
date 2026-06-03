<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Events\BookingApproved;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveBooking
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
                    'booking' => 'Seul le voyageur du trajet peut approuver cette réservation.',
                ]);
            }

            if (! $booking->canBeApprovedByTraveler()) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être approuvée depuis son statut actuel.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::EN_PAIEMENT,
                $actor,
                'Approuvée par le voyageur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip', 'user']);
        });

        event(new BookingApproved($booking));

        return $booking;
    }
}
