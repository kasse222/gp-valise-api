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
            'latitude'    => $this->faker->latitude,     // Ex: 48.8566
            'longitude'   => $this->faker->longitude,    // Ex: 2.3522
            'city'        => $this->faker->city,
            'order_index' => $this->faker->numberBetween(1, 5), // Ordre des étapes (1 = départ, N = arrivée)
        ];
    }
}
