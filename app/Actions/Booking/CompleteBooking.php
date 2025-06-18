<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Status\BookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveBooking
{
    /**
     * Exécute la réservation d'un trajet avec valises.
     */
    public function execute(array $validated): Booking
    {
        return DB::transaction(function () use ($validated) {
            $user = Auth::user();
            $trip = Trip::findOrFail($validated['trip_id']);

            // On s'assure que chaque valise est disponible
            foreach ($validated['items'] as $item) {
                $luggage = Luggage::findOrFail($item['luggage_id']);
                if ($luggage->status !== 'en_attente') {
                    throw ValidationException::withMessages([
                        'items' => ["La valise #{$luggage->id} n'est pas disponible."],
                    ]);
                }
            }

            // Création du booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'status'  => BookingStatus::EN_ATTENTE,
            ]);

            // Création des items + mise à jour des valises
            foreach ($validated['items'] as $item) {
                $luggage = Luggage::findOrFail($item['luggage_id']);

                BookingItem::create([
                    'booking_id'  => $booking->id,
                    'trip_id'     => $trip->id,
                    'luggage_id'  => $luggage->id,
                    'kg_reserved' => $item['kg_reserved'],
                    'price'       => $item['price'],
                ]);

                $luggage->update(['status' => 'reservee']);
            }

            // Historisation du statut initial
            $booking->statusHistories()->create([
                'old_status' => null,
                'new_status' => BookingStatus::EN_ATTENTE,
                'user_id'    => $user->id,
                'reason'     => 'Réservation initiale',
            ]);

            return $booking->load('bookingItems.luggage');
        });
    }
}
