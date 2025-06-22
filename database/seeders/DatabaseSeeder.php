<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ordre important : respecter les dépendances entre tables

        $this->call([
            PlanSeeder::class,
            UserSeeder::class,

            TripSeeder::class,
            LuggageSeeder::class,

            BookingSeeder::class,
            BookingItemSeeder::class,

            PaymentSeeder::class,
            TransactionSeeder::class,

            LocationSeeder::class,
            BookingStatusHistorySeeder::class,

            ReportSeeder::class,
            InvitationSeeder::class,
        ]);
    }
}
