<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\Location;
use App\Enums\LocationTypeEnum;
use App\Enums\LocationPositionEnum;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Trip::all()->each(function (Trip $trip) {
            $locations = [];

            $locations[] = [
                'trip_id'     => $trip->id,
                'latitude'    => fake()->latitude(),
                'longitude'   => fake()->longitude(),
                'city'        => fake()->city(),
                'position'    => LocationPositionEnum::DEPART,
                'type'        => $this->randomTypeForPosition(LocationPositionEnum::DEPART),
                'order_index' => 0,
            ];

            $nbEtapes = rand(0, 3);
            for ($i = 0; $i < $nbEtapes; $i++) {
                $locations[] = [
                    'trip_id'     => $trip->id,
                    'latitude'    => fake()->latitude(),
                    'longitude'   => fake()->longitude(),
                    'city'        => fake()->city(),
                    'position'    => LocationPositionEnum::ETAPE,
                    'type'        => $this->randomTypeForPosition(LocationPositionEnum::ETAPE),
                    'order_index' => $i + 1,
                ];
            }

            $locations[] = [
                'trip_id'     => $trip->id,
                'latitude'    => fake()->latitude(),
                'longitude'   => fake()->longitude(),
                'city'        => fake()->city(),
                'position'    => LocationPositionEnum::ARRIVEE,
                'type'        => $this->randomTypeForPosition(LocationPositionEnum::ARRIVEE),
                'order_index' => count($locations),
            ];

            Location::insert($locations);
        });

        $this->command->info('✔ Locations générées pour chaque trip.');
    }


    protected function randomTypeForPosition(LocationPositionEnum $position): LocationTypeEnum
    {
        return match ($position) {
            LocationPositionEnum::DEPART, LocationPositionEnum::ARRIVEE =>
            fake()->randomElement([LocationTypeEnum::AEROPORT, LocationTypeEnum::VILLE]),

            LocationPositionEnum::ETAPE =>
            fake()->randomElement([LocationTypeEnum::HUB, LocationTypeEnum::ETAPE, LocationTypeEnum::DOUANE]),
        };
    }
}
