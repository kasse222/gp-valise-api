<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Report;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach (range(1, 10) as $i) {
            $reporter = $users->random(); // Utilisateur qui signale
            $targetType = fake()->randomElement(['user', 'trip', 'booking']);

            switch ($targetType) {
                case 'user':
                    $target = User::where('id', '!=', $reporter->id)->inRandomOrder()->first();
                    break;

                case 'trip':
                    $target = Trip::inRandomOrder()->first();
                    break;

                case 'booking':
                    $target = Booking::inRandomOrder()->first();
                    break;
            }

            if ($target) {
                Report::factory()->create([
                    'user_id'     => $reporter->id,
                    'target_type' => get_class($target),
                    'target_id'   => $target->id,
                    'reason'      => fake()->randomElement(['comportement suspect', 'annulation non justifiée', 'arnaque possible']),
                    'comment'     => fake()->sentence(8),
                ]);
            }
        }
    }
}
