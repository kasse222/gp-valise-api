<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name'      => $this->faker->firstName,
            'last_name'       => $this->faker->lastName,
            'email'           => $this->faker->unique()->safeEmail,
            'password'        => Hash::make('password'), // ❗ à overrider en tests si besoin
            'role'            => $this->faker->randomElement(UserRoleEnum::cases())->value,
            'verified_user'   => $this->faker->boolean(80), // 80% des utilisateurs sont vérifiés
            'phone'           => $this->faker->unique()->e164PhoneNumber,
            'country'         => $this->faker->countryCode, // 💡 Plus utile en API (FR, SN, MA...)
            'kyc_passed_at'   => $this->faker->optional(0.6)->dateTimeBetween('-6 months'),
            'plan_id'         => null,
            'plan_expires_at' => null,
        ];
    }

    /**
     * 💼 Administrateur
     */
    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::ADMIN,
        ]);
    }

    /**
     * 📦 Expéditeur
     */
    public function expeditor(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::SENDER,
        ]);
    }

    /**
     * ✈️ Voyageur
     */
    public function traveler(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::TRAVELER,
        ]);
    }
    public function sender(): static
    {
        // alias vers la méthode déjà existante
        return $this->expeditor();
    }
    /**
     * 🧪 Utilisateur vérifié
     */
    public function verified(): static
    {
        return $this->state(fn() => [
            'verified_user' => true,
            'kyc_passed_at' => now()->subDays(rand(5, 90)),
        ]);
    }
}
