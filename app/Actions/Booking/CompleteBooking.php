<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Events\BookingDelivered;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteBooking
{
    public function execute(Booking $booking, User $actor): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor) {
            $booking = Booking::query()
                ->with(['bookingItems.luggage', 'trip'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $trip = $booking->trip()
                ->lockForUpdate()
                ->firstOrFail();

            if ($actor->id !== $trip->user_id) {
                throw ValidationException::withMessages([
                    'booking' => 'Seul le voyageur du trajet peut marquer cette réservation comme livrée.',
                ]);
            }

            if (! $booking->status->canTransitionTo(BookingStatusEnum::LIVREE)) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être marquée comme livrée depuis son statut actuel.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::LIVREE,
                $actor,
                'Livraison confirmée par le voyageur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip']);
        });

        event(new BookingDelivered($booking));

        return $booking;
    }
}
