<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        $this->call([
            DemoDataSeeder::class,
            DemoSeeder::class,
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
            LedgerAccountSeeder::class,
            LedgerDemoSeeder::class,
            DisputeDemoSeeder::class,
        ]);
    }
}
