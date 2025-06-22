<?php

namespace Database\Factories;

use App\Models\Luggage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Enums\LuggageStatusEnum;

class LuggageFactory extends Factory
{
    protected $model = Luggage::class;

    public function definition(): array
    {
        $pickupDate = $this->faker->dateTimeBetween('+1 day', '+1 week');
        $deliveryDate = $this->faker->dateTimeBetween($pickupDate, '+2 weeks');

        return [
            'user_id'        => User::factory()->expeditor(),
            'description'    => $this->faker->sentence(5),
            'weight_kg'      => $this->faker->randomFloat(1, 1, 25),
            'length_cm'      => $this->faker->numberBetween(20, 80),
            'width_cm'       => $this->faker->numberBetween(20, 60),
            'height_cm'      => $this->faker->numberBetween(10, 50),
            'pickup_city'    => $this->faker->city,
            'delivery_city'  => $this->faker->city,
            'pickup_date'    => $pickupDate,
            'delivery_date'  => $deliveryDate,
            'status'         => LuggageStatusEnum::EN_ATTENTE, // conforme à ton enum
            'tracking_id'    => (string) Str::uuid(),
        ];
    }

    public function reservee(): static
    {
        return $this->state(fn() => ['status' => LuggageStatusEnum::RESERVEE]);
    }

    public function enTransit(): static
    {
        return $this->state(fn() => ['status' => LuggageStatusEnum::EN_TRANSIT]);
    }

    public function livree(): static
    {
        return $this->state(fn() => ['status' => LuggageStatusEnum::LIVREE]);
    }

    public function retour(): static
    {
        return $this->state(fn() => ['status' => LuggageStatusEnum::RETOUR]);
    }

    public function annulee(): static
    {
        return $this->state(fn() => ['status' => LuggageStatusEnum::ANNULEE]);
    }

    public function perdue(): static
    {
        return $this->state(fn() => ['status' => LuggageStatusEnum::PERDUE]);
    }
}
