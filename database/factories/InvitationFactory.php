<?php

namespace Database\Factories;

use App\Enums\InvitationStatusEnum;
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
        return [
            'sender_id'       => User::factory()->sender(),
            'recipient_id'    => User::factory()->expeditor(), // 💡 Ajouter recipient_id proprement
            'recipient_email' => $this->faker->unique()->safeEmail(),
            'token'           => Str::uuid(),
            'used_at'         => null,
            'expires_at'      => now()->addDays(rand(3, 7)),
            'status'          => InvitationStatusEnum::PENDING,
            'message'         => $this->faker->optional()->sentence(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'expires_at' => now()->subDay(),
            'status'     => InvitationStatusEnum::EXPIRED,
        ]);
    }

    public function used(): static
    {
        return $this->state(fn() => [
            'used_at' => now()->subMinutes(rand(10, 120)),
            'status'  => InvitationStatusEnum::USED,
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
     * ⏳ Invitation valide (non utilisée, non expirée)
     */
    public function active(): static
    {
        return $this->unused()->state(fn() => [
            'expires_at' => now()->addDays(7),
        ]);
    }
}
