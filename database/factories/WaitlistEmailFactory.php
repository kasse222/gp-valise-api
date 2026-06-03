<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WaitlistEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaitlistEmailFactory extends Factory
{
    protected $model = WaitlistEmail::class;

    public function definition(): array
    {
        return [
            'email'      => $this->faker->unique()->safeEmail(),
            'name'       => $this->faker->name(),
            'role'       => $this->faker->randomElement(['sender', 'traveler', 'curious']),
            'message'    => $this->faker->optional()->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
