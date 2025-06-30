<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create('en_US');
        return [
            'first_name'      => $faker->firstName,
            'last_name'       => $faker->lastName,
            'email'           => $faker->unique()->email,
            'password'        => bcrypt('password'), // ❗ à overrider en tests si besoin
            'role'            => $faker->randomElement(UserRoleEnum::cases())->value,
            'verified_user'   => $faker->boolean(80), // 80% des utilisateurs sont vérifiés
            'phone'           => $faker->unique()->e164PhoneNumber,
            'country'         => $faker->countryCode, // 💡 Plus utile en API (FR, SN, MA...)
            'kyc_passed_at'   => $faker->optional(0.6)->dateTimeBetween('-6 months'),
            'plan_id'         => null,
            'plan_expires_at' => null,
        ];
    }

    protected $casts = [
        'role' => UserRoleEnum::class,
    ];
    /**
     * 💼 Administrateur
     */
    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::ADMIN->value,
        ]);
    }

    /**
     * 📦 Expéditeur
     */
    public function expeditor(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::SENDER->value,
        ]);
    }

    /**
     * ✈️ Voyageur
     */
    public function traveler(): static
    {
        return $this->state(fn() => [
            'role' => UserRoleEnum::TRAVELER->value,
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
