<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRoleEnum;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name'      => $this->faker->firstName,
            'last_name'       => $this->faker->lastName,
            'email'           => $this->faker->unique()->safeEmail,
            'password'        => Hash::make('password'),
            'role'            => $this->faker->randomElement(['admin', 'expeditor', 'traveler']),
            'verified_user'   => $this->faker->boolean(80),
            'phone'           => $this->faker->phoneNumber,
            'country'         => $this->faker->country,
            'kyc_passed_at'   => $this->faker->optional()->dateTimeBetween('-6 months'),
            'plan_id'         => null, // ou Plan::factory() si tu veux les relier
            'plan_expires_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    public function expeditor(): static
    {
        return $this->state(fn() => ['role' => 'expeditor']);
    }

    public function traveler(): static
    {
        return $this->state(fn() => ['role' => 'traveler']);
    }
}
