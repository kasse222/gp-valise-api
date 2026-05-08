<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LuggageStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LuggageSeeder extends Seeder
{
    public function run(): void
    {
        $senders = User::where('role', UserRoleEnum::SENDER->value)->get();
        $trips   = Trip::all();

        if ($senders->isEmpty()) {
            $this->command->warn('⚠ Aucun expéditeur trouvé. LuggageSeeder ignoré.');
            return;
        }

        foreach ($senders as $sender) {
            $count = rand(1, 4);

            for ($i = 0; $i < $count; $i++) {
                $pickupDate   = now()->addDays(rand(1, 10));
                $deliveryDate = (clone $pickupDate)->addDays(rand(2, 5));

                $status = fake()->randomElement(LuggageStatusEnum::cases());

                Luggage::create([
                    'user_id'             => $sender->id,
                    'trip_id'             => $trips->random()->id,
                    'description'         => fake()->words(3, true),
                    'weight_kg'           => fake()->numberBetween(5, 250), // ← kg×10
                    'length_cm'           => fake()->numberBetween(30, 80),
                    'width_cm'            => fake()->numberBetween(20, 60),
                    'height_cm'           => fake()->numberBetween(10, 40),
                    'pickup_city'         => fake()->city(),
                    'delivery_city'       => fake()->city(),
                    'pickup_date'         => $pickupDate,
                    'delivery_date'       => $deliveryDate,
                    'status'              => $status->value,
                    'tracking_id'         => (string) Str::uuid(),
                    'is_fragile'          => fake()->boolean(30),
                    'insurance_requested' => fake()->boolean(20),
                ]);
            }
        }

        $this->command->info('✔ LuggageSeeder terminé avec succès.');
    }
}
