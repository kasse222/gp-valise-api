<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Trip;
use App\Enums\LocationTypeEnum;
use App\Enums\LocationPositionEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $position = fake()->randomElement(LocationPositionEnum::cases());

        // Logique de type en fonction de la position
        $type = match ($position) {
            LocationPositionEnum::DEPART, LocationPositionEnum::ARRIVEE =>
            fake()->randomElement([LocationTypeEnum::AEROPORT, LocationTypeEnum::VILLE]),
            LocationPositionEnum::ETAPE =>
            fake()->randomElement([
                LocationTypeEnum::ETAPE,
                LocationTypeEnum::DOUANE,
                LocationTypeEnum::HUB,
            ]),
        };

        return [
            'trip_id'     => Trip::factory(),
            'latitude'    => fake()->latitude,
            'longitude'   => fake()->longitude,
            'city'        => fake()->city,
            'position'    => $position,
            'type'        => $type,
            'order_index' => 0, // à surcharger depuis le Seeder
        ];
    }
}
