<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Trip;
use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $trips = Trip::all();

        foreach ($trips as $trip) {
            $steps = [
                [
                    'position'    => LocationPositionEnum::DEPART,
                    'type'        => fake()->randomElement([LocationTypeEnum::AEROPORT, LocationTypeEnum::VILLE]),
                ],
                [
                    'position'    => LocationPositionEnum::ETAPE,
                    'type'        => fake()->randomElement([
                        LocationTypeEnum::ETAPE,
                        LocationTypeEnum::HUB,
                        LocationTypeEnum::DOUANE,
                    ]),
                ],
                [
                    'position'    => LocationPositionEnum::ARRIVEE,
                    'type'        => fake()->randomElement([LocationTypeEnum::AEROPORT, LocationTypeEnum::VILLE]),
                ],
            ];

            foreach ($steps as $index => $step) {
                Location::create([
                    'trip_id'     => $trip->id,
                    'latitude'    => fake()->latitude,
                    'longitude'   => fake()->longitude,
                    'city'        => fake()->city,
                    'position'    => $step['position'],
                    'type'        => $step['type'],
                    'order_index' => $index,
                ]);
            }
        }
    }
}
