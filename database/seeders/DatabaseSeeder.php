<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Ordre important pour respecter les dépendances
        $this->call([
            UserSeeder::class,
            TripSeeder::class,
            LuggageSeeder::class,
            BookingSeeder::class,
            PaymentSeeder::class,
            ReportSeeder::class,
        ]);
    }
}
