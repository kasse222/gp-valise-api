<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Enums\TripTypeEnum;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $departureDate = $this->faker->dateTimeBetween('+1 day', '+1 month');

        return [
            'user_id'      => User::factory()->traveler(),
            'departure'    => $this->faker->city,
            'destination'  => $this->faker->city,
            'date'         => $departureDate,
            'capacity'     => $this->faker->numberBetween(5, 40), // en kg
            'status'       => 'actif', // ou null si par défaut
            'type_trip'    => $this->faker->randomElement(['standard', 'express', 'sur_devis']),
            'flight_number' => strtoupper(Str::random(2)) . $this->faker->numberBetween(100, 9999),
        ];
    }

    public function standard(): static
    {
        return $this->state(fn() => ['type_trip' => 'standard']);
    }

    public function express(): static
    {
        return $this->state(fn() => ['type_trip' => 'express']);
    }
}
