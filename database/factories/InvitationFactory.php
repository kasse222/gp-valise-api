<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        $used      = $this->faker->boolean(20); // 20% utilisées
        $expiresAt = Carbon::now()->addDays($this->faker->numberBetween(1, 30));

        return [
            'sender_id'        => User::factory(),
            'recipient_email'  => $this->faker->unique()->safeEmail,
            'token'            => (string) Str::uuid(), // UUID = traçable et sécurisé
            'used_at'          => $used ? now() : null,
            'expires_at'       => $expiresAt,
            'message'          => $this->faker->optional()->sentence(10),
        ];
    }

    /**
     * 📍 Invitation déjà utilisée
     */
    public function used(): static
    {
        return $this->state(fn() => [
            'used_at' => now(),
        ]);
    }

    /**
     * ✅ Invitation non encore utilisée
     */
    public function unused(): static
    {
        return $this->state(fn() => [
            'used_at' => null,
        ]);
    }

    /**
     * ⛔️ Invitation expirée
     */
    public function expired(): static
    {
        return $this->state(fn() => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * ⏳ Invitation valide (non utilisée, non expirée)
     */
    public function active(): static
    {
        return $this->unused()->state(fn() => [
            'expires_at' => now()->addDays(7),
        ]);
    }
}
