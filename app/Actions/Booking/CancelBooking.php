<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Events\BookingCanceled;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function execute(int $bookingId): Booking
    {
        return DB::transaction(function () use ($bookingId) {
            /** @var Booking $booking */
            $booking = Booking::query()
                ->with(['bookingItems.luggage', 'trip', 'user'])
                ->lockForUpdate()
                ->findOrFail($bookingId);

            if (! $booking->status->canBeCancelled()) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être annulée.',
                ]);
            }

            if (! in_array($booking->status, [
                BookingStatusEnum::EN_PAIEMENT,
                BookingStatusEnum::ACCEPTE,
                BookingStatusEnum::PAIEMENT_ECHOUE,
            ], true)) {
                throw ValidationException::withMessages([
                    'booking' => 'Le statut actuel ne permet pas une annulation manuelle.',
                ]);
            }

            $booking->transitionTo(
                BookingStatusEnum::ANNULE,
                auth()->user(),
                'Annulation manuelle de la réservation'
            );

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }
            }

            $booking = $booking->fresh(['bookingItems.luggage', 'trip', 'user', 'statusHistories']);

            event(new BookingCanceled($booking));

            return $booking;
        });
    }
}
