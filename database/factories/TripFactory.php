<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'departure' => fake()->city(),
            'destination' => fake()->city(),
            'date' => fake()->dateTimeBetween('+1 days', '+30 days'),
            'capacity' => fake()->numberBetween(5, 25), // en kg
            'status' => 'actif',
            'flight_number' => 'AF' . fake()->numberBetween(100, 9999),
        ];
    }
}
