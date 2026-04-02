<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Events\BookingDelivered;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteBooking
{
    /**
     * Marquer une réservation comme livrée par le voyageur.
     */
    public function execute(Booking $booking, User $user): Booking
    {
        $booking = DB::transaction(function () use ($booking, $user) {
            $booking = Booking::query()
                ->with(['bookingItems.luggage', 'trip'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if (! $booking->canBeUpdatedTo(BookingStatusEnum::LIVREE, $user)) {
                throw ValidationException::withMessages([
                    'booking' => 'Livraison non autorisée ou statut invalide.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::LIVREE,
                $user,
                'Livraison confirmée par le voyageur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip']);
        });

        event(new BookingDelivered($booking));

        return $booking;
    }
}
