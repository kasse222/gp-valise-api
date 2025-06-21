<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use App\Validators\LuggageValidator;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function reserveTrip(User $user, Trip $trip, array $items): Booking
    {
        return DB::transaction(function () use ($user, $trip, $items) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'status'  => 'en_attente',
            ]);

            foreach ($items as $item) {
                $luggage = Luggage::findOrFail($item['luggage_id']);
                $kgReserved = $item['kg_reserved'];

                app(LuggageValidator::class)->validateReservation($luggage, $trip, $kgReserved);

                BookingItem::create([
                    'booking_id'  => $booking->id,
                    'trip_id'     => $trip->id,
                    'luggage_id'  => $luggage->id,
                    'kg_reserved' => $kgReserved,
                    'price'       => $item['price'],
                ]);

                $luggage->update(['status' => 'reservee']);
            }

            return $booking;
        });
    }
}
