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
            'notes' => $this->faker->sentence(),
        ];
    }
}
