<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteBooking
{
    public function execute(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->with('bookingItems.luggage')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ($booking->status->isFinal()) {
                throw ValidationException::withMessages([
                    'booking' => 'Une réservation finalisée ne peut pas être supprimée.',
                ]);
            }

            if (! $booking->status->canTransitionTo(BookingStatusEnum::ANNULE)) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être supprimée depuis son statut actuel.',
                ]);
            }

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }

                $item->delete();
            }

            $booking->delete();
        });
    }
}
