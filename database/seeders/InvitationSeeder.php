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
        $senders = User::all()->where('role', 3); // SENDER

        foreach ($senders as $sender) {
            Invitation::create([
                'sender_id'       => $sender->id,
                'recipient_email' => fake()->unique()->safeEmail,
                'token'           => Str::uuid(),
                'used_at'         => null,
            ]);
        }
    }
}
