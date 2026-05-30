<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'first_name'      => $faker->firstName,
            'last_name'       => $faker->lastName,
            'email'           => $faker->unique()->safeEmail,
            'phone'           => $faker->unique()->e164PhoneNumber,
            'country'         => $faker->countryCode,
            'password'        => Hash::make('password'),

            // ⚠️ ENUM direct (pas ->value)
            'role'            => $faker->randomElement(UserRoleEnum::cases()),

            'verified_user'   => $faker->boolean(80),
            'kyc_passed_at'   => $faker->optional(0.6)->dateTimeBetween('-6 months'),
            'plan_id'         => null,
            'plan_expires_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::ADMIN,
        ]);
    }

    public function expeditor(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::SENDER,
        ]);
    }

    public function traveler(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::TRAVELER,
        ]);
    }

    public function sender(): static
    {
        return $this->expeditor();
    }

    public function verified(): static
    {
        return $this->state(fn() => [
            'verified_user' => true,
            'kyc_passed_at' => now()->subDays(rand(5, 90)),
        ]);
    }
}
