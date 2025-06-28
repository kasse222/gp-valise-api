<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use App\Models\Location;
use App\Enums\TripTypeEnum;
use App\Enums\TripStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $travelers = User::where('role', UserRoleEnum::TRAVELER)->get();

        $cities = ['Dakar', 'Paris', 'Abidjan', 'Casablanca', 'New York', 'Bruxelles', 'Bamako', 'Madrid'];

        if (count($cities) < 2) {
            $this->command->error('⚠ Liste de villes trop courte.');
            return;
        }

        foreach ($travelers as $traveler) {
            $nbTrips = rand(1, 3);

            for ($i = 0; $i < $nbTrips; $i++) {
                // Tirer deux villes différentes
                $departureCity = fake()->randomElement($cities);
                do {
                    $destinationCity = fake()->randomElement($cities);
                } while ($destinationCity === $departureCity);

                // Créer le trip de base
                $trip = Trip::create([
                    'user_id'       => $traveler->id,
                    'date'          => now()->addDays(rand(5, 60)),
                    'capacity'      => rand(10, 50),
                    'status'        => TripStatusEnum::ACTIVE->value,
                    'type_trip'     => TripTypeEnum::STANDARD->value,
                    'flight_number' => 'FL' . rand(100, 999),
                    'price_per_kg'  => fake()->randomFloat(2, 5, 25),
                ]);

                // Ajout des locations associées (départ + arrivée)
                $trip->locations()->createMany([
                    [
                        'city'        => $departureCity,
                        'latitude'    => fake()->latitude(),
                        'longitude'   => fake()->longitude(),
                        'position'    => LocationPositionEnum::DEPART->value,   // ✅ ici
                        'type'        => LocationTypeEnum::VILLE->value,        // ✅ ici
                        'order_index' => 0,
                    ],
                    [
                        'city'        => $destinationCity,
                        'latitude'    => fake()->latitude(),
                        'longitude'   => fake()->longitude(),
                        'position'    => LocationPositionEnum::ARRIVEE->value,  // ✅ ici
                        'type'        => LocationTypeEnum::VILLE->value,        // ✅ ici
                        'order_index' => 1,
                    ]
                ]);
            }
        }

        $this->command->info('✔ TripSeeder terminé avec succès.');
    }
}
