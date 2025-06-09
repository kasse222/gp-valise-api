<?php

namespace Database\Seeders;

use App\Models\Luggage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LuggageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupère les utilisateurs avec le rôle 'expediteur'
        $expediteurs = User::where('role', 'expediteur')->get();

        // Chaque expéditeur poste 1 à 3 valises
        foreach ($expediteurs as $expediteur) {
            Luggage::factory()
                ->count(rand(1, 3))
                ->create([
                    'user_id' => $expediteur->id,
                ]);
        }
    }
}
