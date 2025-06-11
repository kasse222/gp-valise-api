<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // 📍 Pour chaque trip, on enregistre des points GPS
        $trips = Trip::all();

        foreach ($trips as $trip) {
            // Génère entre 3 et 7 points GPS simulés pour chaque trajet
            Location::factory()
                ->count(rand(3, 7))
                ->state([
                    'trip_id' => $trip->id,
                    'user_id' => $trip->user_id,
                ])
                ->create();
        }
    }
}
