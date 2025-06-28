<?php

namespace Database\Seeders;

use App\Enums\ReportReasonEnum;
use App\Models\Report;
use App\Models\User;
use App\Models\Booking;
use App\Models\Luggage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::inRandomOrder()->take(10)->get();

        $reportables = collect()
            ->merge(Booking::inRandomOrder()->limit(5)->get())   // → get()
            ->merge(Luggage::inRandomOrder()->limit(5)->get());

        if ($reportables->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Aucune donnée disponible pour générer des rapports.');
            return;
        }

        foreach ($reportables as $reportable) {
            Report::create([
                'user_id'         => $users->random()->id,
                'reportable_id'   => $reportable->id,
                'reportable_type' => get_class($reportable),
                'reason'          => fake()->randomElement(ReportReasonEnum::values()),
                'details'         => fake()->realText(100),
            ]);
        }

        $this->command->info('✔ ReportSeeder terminé.');
    }
}
