<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Events\BookingCanceled;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBooking
{
    public function execute(Booking $booking, ?User $actor = null, string $cancelledBy = 'sender'): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor, $cancelledBy) {
            $booking = Booking::query()
                ->with(['bookingItems.luggage', 'trip', 'user'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if (! $booking->status->canTransitionTo(BookingStatusEnum::ANNULE)) {
                throw ValidationException::withMessages([
                    'booking' => 'Cette réservation ne peut pas être annulée depuis son statut actuel.',
                ]);
            }

            // Calcul taux remboursement selon règles métier
            $refundRate = $booking->computeRefundRate($cancelledBy);

            $booking->cancel_reason = match ($cancelledBy) {
                'traveler' => 'Annulation par le voyageur',
                'admin'    => 'Annulation administrative',
                default    => 'Annulation par l\'expéditeur',
            };
            $booking->refund_rate = $refundRate;
            $booking->save();

            $booking->transitionTo(
                BookingStatusEnum::ANNULE,
                $actor,
                $booking->cancel_reason
            );

            foreach ($booking->bookingItems as $item) {
                if ($item->luggage) {
                    $item->luggage->update([
                        'status' => LuggageStatusEnum::EN_ATTENTE,
                    ]);
                }
            }

            return $booking->fresh([
                'bookingItems.luggage',
                'trip',
                'user',
                'statusHistories',
            ]);
        });

        event(new BookingCanceled($booking));

        return $booking;
    }
}
