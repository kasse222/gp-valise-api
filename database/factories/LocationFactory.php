<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\LocationTypeEnum;
use App\Enums\LocationPositionEnum;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'trip_id'     => Trip::factory(), // Peut être surchargé
            'latitude'    => $this->faker->latitude(),
            'longitude'   => $this->faker->longitude(),
            'city'        => $this->faker->city(),
            'position'    => LocationPositionEnum::DEPART, // Par défaut, mais overridable
            'type'        => LocationTypeEnum::AEROPORT,   // Par défaut pour un départ
            'order_index' => 0, // À ajuster manuellement dans les states
        ];
    }

    public function departure(): static
    {
        return $this->state(fn() => [
            'position' => LocationPositionEnum::DEPART,
            'type' => LocationTypeEnum::AEROPORT, // ou VILLE aléatoire
            'order_index' => 0,
        ]);
    }

    public function step(int $i = 1): static
    {
        return $this->state(fn() => [
            'position' => LocationPositionEnum::ETAPE,
            'type' => fake()->randomElement([LocationTypeEnum::HUB, LocationTypeEnum::DOUANE]),
            'order_index' => $i,
        ]);
    }

    public function arrival(int $i = 2): static
    {
        return $this->state(fn() => [
            'position' => LocationPositionEnum::ARRIVEE,
            'type' => LocationTypeEnum::VILLE,
            'order_index' => $i,
        ]);
    }
}
