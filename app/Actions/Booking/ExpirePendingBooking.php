<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpirePendingBooking
{
    public function execute(Booking $booking): Booking
    {
        $booking->refresh();

        if ($booking->status !== BookingStatusEnum::EN_PAIEMENT) {
            throw ValidationException::withMessages([
                'booking' => "Seules les réservations en attente de paiement peuvent expirer.",
            ]);
        }

        if ($booking->payment_expires_at === null || $booking->payment_expires_at->isFuture()) {
            throw ValidationException::withMessages([
                'booking' => "Cette réservation n'est pas encore expirée.",
            ]);
        }

        return DB::transaction(function () use ($booking) {
            $booking->loadMissing('bookingItems.luggage');

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }
            }

            $booking->transitionTo(
                BookingStatusEnum::EXPIREE,
                null,
                'Expiration automatique du paiement'
            );

            $booking->update([
                'payment_expires_at' => null,
            ]);

            return $booking->fresh(['bookingItems.luggage', 'statusHistories']);
        });
    }
}
