<?php

declare(strict_types=1);

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
use App\Events\BookingDeliver;
use App\Events\BookingDelivered;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Confirmation de livraison à destination.
 *
 * Le voyageur scanne le QR ou saisit le code secret présenté par le destinataire.
 *
 * Transition : EN_TRANSIT → LIVREE
 * Effets :
 *   - delivered_at = now()
 *   - escrow_releasable_at = delivered_at + 48h
 */
class ConfirmDelivery
{
    public function execute(Booking $booking, User $actor, string $codeOrToken): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor, $codeOrToken) {
            $booking = Booking::query()
                ->with(['trip', 'bookingItems.luggage'])
                ->lockForUpdate()
                ->findOrFail($booking->id);

            $trip = $booking->trip()
                ->lockForUpdate()
                ->firstOrFail();

            // Seul le traveler peut scanner / valider
            if ($actor->id !== $trip->user_id) {
                throw ValidationException::withMessages([
                    'booking' => 'Seul le voyageur peut confirmer la livraison.',
                ]);
            }

            if (! $booking->status->canTransitionTo(BookingStatusEnum::LIVREE)) {
                throw ValidationException::withMessages([
                    'booking' => "La livraison n'est pas possible depuis le statut {$booking->status->value}.",
                ]);
            }

            // Vérification code secret OU QR token
            $validCode  = $booking->verifyDeliveryCode($codeOrToken);
            $validToken = $booking->verifyDeliveryQrToken($codeOrToken);

            if (! $validCode && ! $validToken) {
                throw ValidationException::withMessages([
                    'code' => 'Code ou QR invalide — livraison refusée.',
                ]);
            }

            $booking->markDelivered();

            $booking->transitionTo(
                BookingStatusEnum::LIVREE,
                $actor,
                'Livraison confirmée par scan QR / code secret'
            );

            return $booking->fresh(['bookingItems.luggage', 'trip', 'user']);
        });

        event(new BookingDelivered($booking));

        return $booking;
    }
}
