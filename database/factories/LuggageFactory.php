<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LuggageStatusEnum;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class LuggageFactory extends Factory
{
    protected $model = Luggage::class;

    public function definition(): array
    {
        $pickupDate   = $this->faker->dateTimeBetween('+1 day', '+1 week');
        $deliveryDate = $this->faker->dateTimeBetween($pickupDate, '+2 weeks');

        return [
            'user_id'             => User::factory()->expeditor(),
            'trip_id'             => Trip::factory(),
            'description'         => $this->faker->sentence(5),
            'weight_kg'           => $this->faker->numberBetween(5, 250), // ← kg×10 : 0.5kg→25kg
            'length_cm'           => $this->faker->numberBetween(20, 80),
            'width_cm'            => $this->faker->numberBetween(20, 60),
            'height_cm'           => $this->faker->numberBetween(10, 50),
            'pickup_city'         => $this->faker->city(),
            'delivery_city'       => $this->faker->city(),
            'pickup_date'         => $pickupDate,
            'delivery_date'       => $deliveryDate,
            'status'              => $this->faker->randomElement(LuggageStatusEnum::cases()),
            'tracking_id'         => (string) Str::uuid(),
            'is_fragile'          => $this->faker->boolean(30),
            'insurance_requested' => $this->faker->boolean(20),
        ];
    }

    public function disponible(): static
    {
        return $this->state(['status' => LuggageStatusEnum::EN_ATTENTE]);
    }

    public function reservee(): static
    {
        return $this->state(['status' => LuggageStatusEnum::RESERVEE]);
    }

    public function enTransit(): static
    {
        return $this->state(['status' => LuggageStatusEnum::EN_TRANSIT]);
    }

    public function livree(): static
    {
        return $this->state(['status' => LuggageStatusEnum::LIVREE]);
    }

    public function retour(): static
    {
        return $this->state(['status' => LuggageStatusEnum::RETOUR]);
    }

    public function annulee(): static
    {
        return $this->state(['status' => LuggageStatusEnum::ANNULEE]);
    }

    public function perdue(): static
    {
        return $this->state(['status' => LuggageStatusEnum::PERDUE]);
    }
}
