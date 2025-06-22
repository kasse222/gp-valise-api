<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class BookingItemSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::all();
        $luggages = Luggage::all();
        $trips    = Trip::all();

        // Sécurité : ne rien faire si vide
        if ($bookings->isEmpty() || $luggages->isEmpty() || $trips->isEmpty()) {
            $this->command->warn('BookingItemSeeder : données insuffisantes. Seeder ignoré.');
            return;
        }

        foreach ($bookings as $booking) {
            // 🎯 On ne récupère que les valises appartenant à l'expéditeur du booking
            $validLuggages = $luggages->where('user_id', $booking->user_id);

            // 🛑 Skip si aucun bagage valide trouvé
            if ($validLuggages->isEmpty()) {
                $this->command->warn("Aucune valise pour l'utilisateur #{$booking->user_id} → Booking #{$booking->id} ignoré.");
                continue;
            }

            // ✅ Prend 1 ou 2 valises selon dispo
            $count = min(rand(1, 2), $validLuggages->count());
            $sampleLuggages = $validLuggages->random($count);

            foreach ($sampleLuggages as $luggage) {
                BookingItem::factory()->create([
                    'booking_id'  => $booking->id,
                    'luggage_id'  => $luggage->id,
                    'trip_id'     => $booking->trip_id,
                    'kg_reserved' => fake()->randomFloat(1, 1, 15),
                    'price'       => fake()->randomFloat(2, 5, 100),
                ]);
            }
        }


        $this->command->info('✔ BookingItemSeeder terminé.');
    }
}
