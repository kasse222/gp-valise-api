<?php

namespace Database\Seeders;

use App\Models\Luggage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Enums\LuggageStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Trip;

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
            $count = rand(1, 4); // chaque expéditeur peut avoir 1 à 4 valises

            for ($i = 0; $i < $count; $i++) {
                $pickupDate = now()->addDays(rand(1, 10));
                $deliveryDate = (clone $pickupDate)->addDays(rand(2, 5));

                $length = fake()->numberBetween(30, 80);
                $width  = fake()->numberBetween(20, 60);
                $height = fake()->numberBetween(10, 40);
                $volume = $length * $width * $height;

                $status = fake()->randomElement(LuggageStatusEnum::cases());

                Luggage::create([
                    'user_id'             => $sender->id,
                    'trip_id'             => $trips->random()->id, // ✅ lien valide
                    'description'         => fake()->words(3, true),
                    'weight_kg'           => fake()->randomFloat(1, 2, 25),
                    'length_cm'           => $length,
                    'width_cm'            => $width,
                    'height_cm'           => $height,
                    //  'volume_cm3'          => $volume,
                    'pickup_city'         => fake()->city(),
                    'delivery_city'       => fake()->city(),
                    'pickup_date'         => $pickupDate,
                    'delivery_date'       => $deliveryDate,
                    'status'              => $status->value,
                    'tracking_id'         => (string) Str::uuid(),
                    'is_fragile'          => fake()->boolean(30),         // 30% fragile
                    'insurance_requested' => fake()->boolean(20),         // 20% demandent assurance
                ]);
            }
        }

        $this->command->info('✔ LuggageSeeder terminé avec succès.');
    }
}
