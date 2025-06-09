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
            'user_id' => User::factory()->voyageurs(), // uniquement voyageurs
            'departure' => fake()->city(),
            'destination' => fake()->city(),
            'date' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'capacity' => fake()->numberBetween(5, 23),
            'status' => 'actif', // par défaut
        ];
    }
}
