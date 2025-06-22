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
        return [
            'sender_id'        => User::factory(),
            'recipient_email'  => $this->faker->unique()->safeEmail,
            'token'            => Str::uuid(), // ou token aléatoire sécurisé
            'used_at'          => $this->faker->boolean(20) ? now() : null, // 20% des invitations sont "utilisées"
        ];
    }
}
