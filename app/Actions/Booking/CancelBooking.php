<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function execute(Booking $booking, User $actor): Booking
    {
        return DB::transaction(function () use ($booking, $actor) {
            $booking = Booking::query()
                ->with(['bookingItems.luggage', 'trip'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if (! $booking->canBeUpdatedTo(BookingStatusEnum::ANNULE, $actor)) {
                throw ValidationException::withMessages([
                    'booking' => 'Annulation non autorisée ou statut invalide.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::ANNULE,
                $actor,
                'Annulation par l’utilisateur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip']);
        });
    }
}
