<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatusEnum;
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
     *
     * @param array $data
     */
    public function execute(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $trip = Trip::findOrFail($data['trip_id']);

            foreach ($data['items'] as $item) {
                $luggage = Luggage::findOrFail($item['luggage_id']);

                if ($luggage->status !== 'en_attente') {
                    throw ValidationException::withMessages([
                        'items' => ["La valise #{$luggage->id} n'est pas disponible."],
                    ]);
                }
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'status'  => BookingStatusEnum::EN_ATTENTE,
            ]);

            foreach ($data['items'] as $item) {
                $luggage = Luggage::findOrFail($item['luggage_id']);

                BookingItem::create([
                    'booking_id'  => $booking->id,
                    'luggage_id'  => $luggage->id,
                    'kg_reserved' => $item['kg_reserved'],
                    'price'       => $item['price'],
                ]);

                $luggage->update(['status' => 'reservee']);
            }

            $booking->statusHistories()->create([
                'old_status' => null,
                'new_status' => BookingStatusEnum::EN_ATTENTE,
                'user_id'    => $user->id,
                'reason'     => 'Réservation initiale',
            ]);

            return $booking->load('bookingItems.luggage');
        });
    }
}
