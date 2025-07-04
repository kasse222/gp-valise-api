<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use App\Enums\TripTypeEnum;
use App\Enums\TripStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->traveler(), // 💡 Laisse Laravel gérer la création
            'departure'      => $this->faker->city() . ', ' . $this->faker->countryCode(),
            'destination'    => $this->faker->city() . ', ' . $this->faker->countryCode(),
            'date' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'capacity' => $this->faker->randomFloat(1, 10, 50),
            'status' => TripStatusEnum::ACTIVE->value,
            'type_trip' => TripTypeEnum::STANDARD->value,      // ✅ idem
            'flight_number' => strtoupper(Str::random(2)) . $this->faker->numberBetween(100, 9999),
            'price_per_kg' => $this->faker->randomFloat(2, 5, 30),
        ];
    }

    /**
     * 🧪 Ajoute automatiquement des localisations cohérentes à ce trip
     */
    public function withLocations(int $steps = 0): static
    {
        return $this->afterCreating(function (Trip $trip) use ($steps) {
            // 👣 Départ
            $trip->locations()->create(
                \Database\Factories\LocationFactory::new()->departure()->make()->toArray()
            );

            // 🔁 Étapes intermédiaires (douane, hub…)
            for ($i = 1; $i <= $steps; $i++) {
                $trip->locations()->create(
                    \Database\Factories\LocationFactory::new()->step($i)->make()->toArray()
                );
            }

            // 🎯 Arrivée
            $trip->locations()->create(
                \Database\Factories\LocationFactory::new()->arrival($steps + 1)->make()->toArray()
            );
        });
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
