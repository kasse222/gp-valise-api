<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Enums\TripTypeEnum;
use App\Enums\TripStatusEnum;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $departureDate = $this->faker->dateTimeBetween('+1 day', '+1 month');

        return [
            'user_id'       => User::factory()->traveler(), // 👈 Assure-toi que traveler() existe bien
            'departure'     => $this->faker->city,
            'destination'   => $this->faker->city,
            'date'          => $departureDate,
            'capacity'      => $this->faker->randomFloat(1, 10, 50), // en kg, plus précis
            'status'        => TripStatusEnum::ACTIVE, // 👈 Enum si présent
            'type_trip'     => $this->faker->randomElement(TripTypeEnum::cases()),
            'flight_number' => strtoupper(Str::random(2)) . $this->faker->numberBetween(100, 9999),
        ];
    }

    public function standard(): static
    {
        return $this->state(fn() => ['type_trip' => TripTypeEnum::STANDARD]);
    }

    public function express(): static
    {
        return $this->state(fn() => ['type_trip' => TripTypeEnum::EXPRESS]);
    }

    public function surDevis(): static
    {
        return $this->state(fn() => ['type_trip' => TripTypeEnum::SUR_DEVIS]);
    }

    public function passé(): static
    {
        return $this->state(fn() => [
            'date' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ]);
    }
}
