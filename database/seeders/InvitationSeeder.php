<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvitationSeeder extends Seeder
{
    public function run(): void
    {
        // 🧑‍✈️ Tous les utilisateurs expéditeurs
        $senders = User::where('role', UserRoleEnum::SENDER)->get();

        foreach ($senders as $sender) {
            // Génère 1 à 3 invitations par expéditeur
            $count = rand(1, 3);

            Invitation::factory()
                ->count($count)
                ->state(function () use ($sender) {
                    // 50 % des cas : invité déjà inscrit
                    $recipient = rand(0, 1)
                        ? User::inRandomOrder()->first()
                        : null;

                    return [
                        'sender_id'       => $sender->id,
                        'recipient_id'    => $recipient?->id,
                        'recipient_email' => $recipient?->email ?? fake()->unique()->safeEmail(),
                    ];
                })
                ->create();
        }

        // 🔁 Invitations expirées (sans destinataire connu)
        Invitation::factory()
            ->count(5)
            ->expired()
            ->state(fn() => [
                'recipient_id'    => null,
                'recipient_email' => fake()->unique()->safeEmail(),
            ])
            ->create();

        // 🔁 Invitations déjà utilisées (destinataire connu ou inconnu)
        Invitation::factory()
            ->count(5)
            ->used()
            ->state(function () {
                $recipient = rand(0, 1)
                    ? User::inRandomOrder()->first()
                    : null;

                return [
                    'recipient_id'    => $recipient?->id,
                    'recipient_email' => $recipient?->email ?? fake()->unique()->safeEmail(),
                ];
            })
            ->create();
    }
}
