<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(PlanTypeEnum::cases());

        return [
            'name'                 => ucfirst($type->value) . ' Plan',
            'price'                => match ($type) {
                PlanTypeEnum::FREE      => 0,
                PlanTypeEnum::BASIC     => 9.99,
                PlanTypeEnum::PREMIUM   => 19.99,
                PlanTypeEnum::ENTREPRISE => 49.99,
            },
            'type'                 => $type->value,
            'features'             => $this->faker->randomElements([
                'booking_priority',
                'extra_storage',
                'support_24_7',
                'custom_reports',
                'API_access',
            ], rand(1, 4)),
            'duration_days'        => $this->faker->randomElement([30, 90, 365]),
            'discount_percent'     => $this->faker->optional()->numberBetween(0, 30),
            'discount_expires_at'  => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'is_active'            => $this->faker->boolean(90),
        ];
    }
}
