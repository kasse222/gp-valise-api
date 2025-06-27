<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Exécution de tous les seeders de manière ordonnée.
     */
    public function run(): void
    {
        // 🧭 Ordre logique : Plans > Utilisateurs > Modules liés
        $this->call([
            PlanSeeder::class,
            UserSeeder::class,
            TripSeeder::class,
            BookingSeeder::class,
            LuggageSeeder::class,
            LocationSeeder::class,
            PaymentSeeder::class,
            TransactionSeeder::class,
            ReportSeeder::class,
            InvitationSeeder::class,
        ]);
    }
}
