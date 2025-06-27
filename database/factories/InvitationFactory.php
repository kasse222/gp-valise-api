<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        $used = $this->faker->boolean(20); // 20% des invitations utilisées

        return [
            'sender_id'        => User::factory(),
            'recipient_email'  => $this->faker->unique()->safeEmail,
            'token'            => Str::uuid(), // UUID = traçable et sécurisé
            'used_at'          => $used ? now() : null,
        ];
    }

    /**
     * Invitation déjà utilisée
     */
    public function used(): static
    {
        return $this->state(fn() => [
            'used_at' => now(),
        ]);
    }

    /**
     * Invitation encore valide
     */
    public function unused(): static
    {
        return $this->state(fn() => [
            'used_at' => null,
        ]);
    }
}
