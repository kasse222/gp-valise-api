<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Enums\PlanTypeEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPlan = Plan::where('type', PlanTypeEnum::FREE->value)->first();

        if (!$defaultPlan) {
            $this->command->error('⚠ Aucun plan FREE trouvé. Seeder annulé.');
            return;
        }

        // ✅ 1. Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name'      => 'Super',
                'last_name'       => 'Admin',
                'phone'           => '+212600000001',
                'country'         => 'MA',
                'password'        => Hash::make('password'),
                'role'            => UserRoleEnum::ADMIN->value,
                'verified_user'   => true,
                //      'phone_verified_at' => now()->addMonth(),
                'plan_id'         => $defaultPlan->id,
                'plan_expires_at' => now()->addMonth(),
            ]
        );

        // ✅ 2. Voyageur
        User::factory()->create([
            'email'         => 'voyageur@example.com',
            'role'          => UserRoleEnum::TRAVELER->value,
            'verified_user' => true,
            //    'phone_verified_at' => now()->addMonth(),
            'plan_id'       => $defaultPlan->id,
        ]);

        // ✅ 3. Expéditeur
        User::factory()->create([
            'email'         => 'expediteur@example.com',
            'role'          => UserRoleEnum::SENDER->value,
            'verified_user' => true,
            //   'phone_verified_at' => now()->addMonth(),
            'plan_id'       => $defaultPlan->id,
        ]);

        // ✅ 4. Génération de 10 utilisateurs avec rôles aléatoires
        collect(range(1, 10))->each(function () use ($defaultPlan) {
            User::factory()->create([
                'role'          => fake()->randomElement([
                    UserRoleEnum::TRAVELER->value,
                    UserRoleEnum::SENDER->value,
                ]),
                'plan_id'       => $defaultPlan->id,
                'verified_user' => fake()->boolean(80),
                //    'phone_verified_at' => fake()->boolean(80) ? now()->subDays(rand(1, 30)) : null,
            ]);
        });

        $this->command->info('✔ UserSeeder terminé avec succès.');
    }
}
