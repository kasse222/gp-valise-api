<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Luggage>
 */
class LuggageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(6),
            'weight' => $this->faker->randomFloat(1, 1, 25), // 1 à 25 kg
            'dimensions' => $this->faker->randomElement(['50x30x20', '55x40x23', '60x45x25']),
            'pickup_city' => $this->faker->city(),
            'delivery_city' => $this->faker->city(),
            'pickup_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'delivery_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'status' => 'en_attente',
        ];
    }
}
