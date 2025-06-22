<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Luggage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        // Liste des modèles possibles à "reporter"
        $reportables = [
            Booking::class,
            Trip::class,
            Luggage::class,
        ];

        // Choix aléatoire d’un modèle cible
        $reportableType = $this->faker->randomElement($reportables);
        $reportable = $reportableType::factory()->create(); // Génère une instance

        return [
            'user_id'         => User::factory(),
            'reportable_id'   => $reportable->id,
            'reportable_type' => $reportableType,
            'reason'          => $this->faker->randomElement([
                'contenu inapproprié',
                'arnaque suspectée',
                'informations fausses',
                'communication agressive',
            ]),
            'details'         => $this->faker->paragraph(),
        ];
    }
}
