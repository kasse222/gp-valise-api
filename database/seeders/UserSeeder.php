<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRoleEnum;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPlan = Plan::where('type', 'free')->first();

        // ✅ Utilisateur admin
        User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'first_name'      => 'Super',
            'last_name'       => 'Admin',
            'phone'           => '+212600000001',
            'country'         => 'MA',
            'password'        => Hash::make('password'),
            'role'            => UserRoleEnum::ADMIN->value,
            'verified_user'   => true,
            'plan_id'         => $defaultPlan?->id,
            'plan_expires_at' => now()->addMonth(),
        ]);

        // ✅ Voyageur
        User::factory()->create([
            'email'         => 'voyageur@example.com',
            'role'          => UserRoleEnum::TRAVELER->value,
            'verified_user' => true,
            'plan_id'       => $defaultPlan?->id,
        ]);

        // ✅ Expéditeur
        User::factory()->create([
            'email'         => 'expediteur@example.com',
            'role'          => UserRoleEnum::SENDER->value,
            'verified_user' => true,
            'plan_id'       => $defaultPlan?->id,
        ]);

        // 🔁 Génération de 10 utilisateurs aléatoires (rôle aléatoire entre 2 et 3)
        User::factory(10)->create([
            'role' => fake()->randomElement([
                UserRoleEnum::TRAVELER->value,
                UserRoleEnum::SENDER->value,
            ]),
        ]);
    }
}
