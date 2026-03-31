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
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->with('bookingItems.luggage')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ($booking->status !== BookingStatusEnum::EN_PAIEMENT) {
                return $booking->fresh(['bookingItems.luggage', 'statusHistories']);
            }

            if ($booking->payment_expires_at === null || $booking->payment_expires_at->isFuture()) {
                return $booking->fresh(['bookingItems.luggage', 'statusHistories']);
            }

            if (! $booking->canTransitionTo(BookingStatusEnum::EXPIREE)) {
                return $booking->fresh(['bookingItems.luggage', 'statusHistories']);
            }

            $booking->transitionTo(
                BookingStatusEnum::EXPIREE,
                null,
                'Expiration automatique du paiement'
            );

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }
            }

            return $booking->fresh(['bookingItems.luggage', 'statusHistories']);
        });
    }
}
