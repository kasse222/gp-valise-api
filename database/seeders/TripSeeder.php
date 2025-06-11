<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupère les utilisateurs avec le rôle 'voyageur'
        $voyageurs = User::where('role', 'voyageur')->get();

        // Pour chaque voyageur, créer entre 1 et 3 trajets
        foreach ($voyageurs as $voyageur) {
            Trip::factory()
                ->count(rand(1, 3))
                ->for($voyageur)
                ->create();
        }
    }
}
