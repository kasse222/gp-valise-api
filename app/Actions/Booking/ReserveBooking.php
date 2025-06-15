<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
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

            $first = $validated['items'][0];
            $luggage = Luggage::findOrFail($first['luggage_id']);
            if ($luggage->status !== 'en_attente') {
                throw ValidationException::withMessages([
                    'items' => ["La valise #{$luggage->id} n'est pas disponible."],
                ]);
            }

            $booking = Booking::create([
                'user_id'    => $user->id,
                'trip_id'    => $trip->id,
                'luggage_id' => $luggage->id, // ← Bien renseigné
                'status'     => 'en_attente',
            ]);

            foreach ($validated['items'] as $item) {
                $l = Luggage::findOrFail($item['luggage_id']);
                if ($l->status !== 'en_attente') {
                    throw ValidationException::withMessages([
                        'items' => ["La valise #{$l->id} n'est pas disponible."]
                    ]);
                }

                BookingItem::create([
                    'booking_id'  => $booking->id,
                    'trip_id'     => $trip->id,
                    'luggage_id'  => $l->id,
                    'kg_reserved' => $item['kg_reserved'],
                    'price'       => $item['price'],
                ]);

                $l->update(['status' => 'reservee']);
            }

            return $booking->load('bookingItems.luggage');
        });
    }
}
