<?php

namespace Database\Seeders;

use App\Models\Luggage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Enums\LuggageStatusEnum;

class LuggageSeeder extends Seeder
{
    public function run(): void
    {
        $senders = User::where('role', \App\Enums\UserRoleEnum::SENDER->value)->get();

        foreach ($senders as $sender) {
            $nb = rand(1, 3); // Chaque expéditeur peut avoir 1 à 3 valises
            for ($i = 0; $i < $nb; $i++) {
                $pickupDate = Carbon::now()->addDays(rand(1, 10));
                $deliveryDate = (clone $pickupDate)->addDays(rand(1, 5));
                $status = fake()->randomElement(LuggageStatusEnum::cases());



                Luggage::create([
                    'user_id'       => $sender->id,
                    'description'   => fake()->sentence(),
                    'weight_kg'     => fake()->randomFloat(1, 1, 20),
                    'length_cm'     => fake()->numberBetween(30, 100),
                    'width_cm'      => fake()->numberBetween(20, 60),
                    'height_cm'     => fake()->numberBetween(10, 50),
                    'pickup_city'   => fake()->city(),
                    'delivery_city' => fake()->city(),
                    'pickup_date'   => $pickupDate,
                    'delivery_date' => $deliveryDate,
                    'status'        => $status->value,
                    'tracking_id'   => fake()->uuid(),
                ]);
            }
        }
    }
}
