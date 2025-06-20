<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal (accès test)
        User::factory()->create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'email'      => 'admin@gp-valise.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin',
            'verified_user' => true,
            'country'    => 'France',
            'phone'      => '+33600000000',
            'kyc_passed_at' => now(),
        ]);

        // 5 voyageurs
        User::factory()->count(5)->state(['role' => 'voyageur'])->create();

        // 5 expéditeurs
        User::factory()->count(5)->state(['role' => 'expediteur'])->create();

        // 4 autres admins
        User::factory()->count(4)->state(['role' => 'admin'])->create();
    }
}
