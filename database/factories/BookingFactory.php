<?php

namespace Database\Factories;

use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'luggage_id' => Luggage::factory(),
            'status' => 'en_attente',
            'total_weight_kg' => $this->faker->randomFloat(1, 1, 100),
            'notes' => $this->faker->sentence(),

        ];
    }
}
