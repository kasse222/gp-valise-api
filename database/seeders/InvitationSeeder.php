<?php

namespace Database\Seeders;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvitationSeeder extends Seeder
{
    public function run(): void
    {
        $senders = User::where('role', \App\Enums\UserRoleEnum::SENDER->value)->get();

        foreach ($senders as $sender) {
            // ✅ 1 à 3 invitations par expéditeur
            $count = rand(1, 3);

            Invitation::factory()
                ->count($count)
                ->state(fn() => [
                    'sender_id' => $sender->id,
                ])
                ->create();
        }

        // 🔁 Générer quelques invitations expirées
        Invitation::factory()
            ->count(5)
            ->expired()
            ->create();

        // 🔁 Générer quelques invitations déjà utilisées
        Invitation::factory()
            ->count(5)
            ->used()
            ->create();
    }
}
