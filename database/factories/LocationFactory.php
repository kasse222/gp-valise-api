<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'trip_id'     => Trip::factory(),
            'latitude'    => $this->faker->latitude(14.0, 50.0),      // Bornes optionnelles pour éviter des extrêmes
            'longitude'   => $this->faker->longitude(-10.0, 40.0),
            'city'        => $this->faker->city,
            'order_index' => $this->faker->unique()->numberBetween(1, 5), // 👈 unique() utile dans certains tests
        ];
    }

    /**
     * Point de départ (optionnel pour tests ciblés)
     */
    public function departure(): static
    {
        return $this->state(fn() => ['order_index' => 1]);
    }

    /**
     * Point d’arrivée (utile pour simuler l’étape finale)
     */
    public function arrival(): static
    {
        return $this->state(fn() => ['order_index' => 5]);
    }
}
