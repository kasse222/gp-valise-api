<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $trips = Trip::all();

        foreach ($trips as $trip) {
            Location::create([
                'trip_id'     => $trip->id,
                'latitude'    => fake()->latitude,
                'longitude'   => fake()->longitude,
                'city'        => fake()->city,
                'order_index' => 1,
            ]);

            Location::create([
                'trip_id'     => $trip->id,
                'latitude'    => fake()->latitude,
                'longitude'   => fake()->longitude,
                'city'        => fake()->city,
                'order_index' => 2,
            ]);
        }
    }
}
