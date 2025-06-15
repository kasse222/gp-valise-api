<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Luggage>
 */
class LuggageFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'user_id' => User::factory(),
            'description'    => $faker->sentence(),
            'weight_kg'      => $faker->randomFloat(1, 1, 25),
            'dimensions'     => $faker->randomElement(['50x30x20', '55x40x23', '60x45x25']),
            'pickup_city'    => $faker->city(),
            'delivery_city'  => $faker->city(),
            'pickup_date'    => $faker->dateTimeBetween('now', '+1 week'),
            'delivery_date'  => $faker->dateTimeBetween('+1 week', '+2 months'),
            'status'         => 'en_attente',
        ];
    }
}
