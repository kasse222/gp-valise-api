<?php

namespace Database\Factories;

use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use App\Status\BookingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{

    public function definition(): array
    {

        return [
            'user_id' => User::factory(),
            'trip_id' => Trip::factory(),
            'luggage_id' => null,
            'status' => BookingStatus::EN_ATTENTE->value,
            'total_weight_kg' => $this->faker->randomFloat(1, 1, 100),
            'notes' => $this->faker->sentence(),

        ];
    }
}
