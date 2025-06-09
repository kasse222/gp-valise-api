<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On récupère tous les trajets disponibles (status actif)
        $trips = Trip::where('status', 'actif')->get();

        foreach ($trips as $trip) {
            // Pour chaque trip, on essaie de réserver 1 à 2 valises disponibles
            $luggages = Luggage::where('status', 'en_attente')
                ->where('pickup_city', $trip->departure)
                ->where('delivery_city', $trip->destination)
                ->inRandomOrder()
                ->take(rand(1, 2))
                ->get();

            foreach ($luggages as $luggage) {
                Booking::factory()->create([
                    'trip_id'    => $trip->id,
                    'luggage_id' => $luggage->id,
                    'status'     => 'en_attente',
                ]);

                // On marque la valise comme réservée
                $luggage->update(['status' => 'reservee']);
            }
        }
    }
}
