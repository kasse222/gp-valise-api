<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Events\BookingHandedOver;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Remise physique du colis : sender → traveler au point de RDV.
 *
 * Transition : CONFIRMEE → EN_TRANSIT
 * Effets :
 *   - handed_over_at = now()
 *   - delivery_code (6 chiffres) généré
 *   - delivery_qr_token (UUID) généré
 *   → événement BookingHandedOver → notification destinataire (email + SMS)
 */
class HandOverBooking
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

            // Seul le traveler du trip peut confirmer la remise
            if ($actor->id !== $trip->user_id) {
                throw ValidationException::withMessages([
                    'booking' => 'Seul le voyageur du trajet peut confirmer la remise du colis.',
                ]);
            }

            if (! $booking->status->canTransitionTo(BookingStatusEnum::EN_TRANSIT)) {
                throw ValidationException::withMessages([
                    'booking' => "La remise n'est pas possible depuis le statut {$booking->status->value}.",
                ]);
            }

            // Générer QR token + code secret AVANT la transition
            $booking->markHandedOver();

            $booking->transitionTo(
                BookingStatusEnum::EN_TRANSIT,
                $actor,
                'Remise physique confirmée par le voyageur'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip', 'user']);
        });

        // Notifie le destinataire avec QR + code secret
        event(new BookingHandedOver($booking));

        return $booking;
    }
}
