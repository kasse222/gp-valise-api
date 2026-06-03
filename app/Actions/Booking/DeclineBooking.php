<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Events\BookingDeclined;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeclineBooking
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
                    'booking' => 'Seul le voyageur du trajet peut refuser cette réservation.',
                ]);
            }

            if (! $booking->canBeDeclinedByTraveler()) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être refusée depuis son statut actuel.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::DECLINED_BY_TRAVELER,
                $actor,
                'Refusée par le voyageur'
            );

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }
            }

            return $booking->fresh(['bookingItems.luggage', 'trip', 'user']);
        });

        event(new BookingDeclined($booking));

        return $booking;
    }
}
