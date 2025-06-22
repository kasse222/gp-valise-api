<?php

namespace Database\Seeders;

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
            ->merge(Booking::inRandomOrder()->take(5)->get())
            ->merge(Luggage::inRandomOrder()->take(5)->get());

        foreach ($reportables as $reportable) {
            Report::create([
                'user_id'         => $users->random()->id,
                'reportable_id'   => $reportable->id,
                'reportable_type' => get_class($reportable),
                'reason'          => fake()->randomElement([
                    'comportement abusif',
                    'valise non livrée',
                    'communication inappropriée',
                    'escroquerie suspectée',
                ]),
                'details'         => fake()->sentence(12),
            ]);
        }
    }
}
