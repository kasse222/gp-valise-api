<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingItem>
 */
class BookingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id'   => null, // assigné depuis le test
            'luggage_id'   => null, // assigné depuis le test
            'trip_id'      => null, // optionnel, dérivable de Booking
            'kg_reserved'  => $this->faker->randomFloat(1, 1, 20),
            'price'        => $this->faker->randomFloat(2, 10, 200),
        ];
    }
}
