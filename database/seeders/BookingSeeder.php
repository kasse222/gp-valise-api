<?php

namespace Database\Seeders;


use App\Models\Trip;
use App\Models\Luggage;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $trips = Trip::where('status', 'actif')->get();

        foreach ($trips as $trip) {
            $remainingCapacity = $trip->capacity;

            $luggages = Luggage::where('status', 'en_attente')
                ->where('pickup_city', $trip->departure)
                ->where('delivery_city', $trip->destination)
                ->inRandomOrder()
                ->get();

            // On initie une réservation vide
            $booking = Booking::factory()->create([
                'trip_id' => $trip->id,
                'status'  => 'en_attente',
            ]);

            foreach ($luggages as $luggage) {
                $weight = $luggage->weight;

                if ($remainingCapacity >= $weight) {
                    BookingItem::create([
                        'booking_id'  => $booking->id,
                        'trip_id'     => $trip->id,
                        'luggage_id'  => $luggage->id,
                        'kg_reserved' => $weight,
                        'price'       => rand(50, 200),
                    ]);

                    // Marquer la valise comme réservée
                    $luggage->update(['status' => 'reservee']);

                    // Déduire du reste de capacité
                    $remainingCapacity -= $weight;
                }
            }

            // Si aucune valise liée, on supprime la réservation vide
            if ($booking->bookingItems()->count() === 0) {
                $booking->delete();
            }
        }
    }
}
