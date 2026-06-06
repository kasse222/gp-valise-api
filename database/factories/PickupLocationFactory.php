<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PickupLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupLocationFactory extends Factory
{
    protected $model = PickupLocation::class;

    public function definition(): array
    {
        $lat = $this->faker->latitude(30, 36);
        $lng = $this->faker->longitude(-10, 0);

        return [
            'latitude'              => $lat,
            'longitude'             => $lng,
            'approximate_latitude'  => $lat + (rand(-5, 5) / 1000),
            'approximate_longitude' => $lng + (rand(-5, 5) / 1000),
            'address'               => $this->faker->streetAddress(),
            'city'                  => $this->faker->city(),
            'instructions'          => $this->faker->optional()->sentence(),
        ];
    }
}
