<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'trip_id' => Trip::inRandomOrder()->first()?->id ?? Trip::factory(),
            'latitude' => $this->faker->latitude(35, 50),     // Ex. : Europe/Afrique du Nord
            'longitude' => $this->faker->longitude(-10, 10),  // Ex. : Europe/Afrique de l’Ouest
            'recorded_at' => now()->subMinutes(rand(1, 120)), // Timestamp récent
        ];
    }
}
