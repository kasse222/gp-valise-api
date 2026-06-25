<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use App\Enums\TripStatusEnum;
use App\Enums\TripTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $travelers = User::where('role', UserRoleEnum::TRAVELER)->get();

        $cities = ['Dakar', 'Paris', 'Abidjan', 'Casablanca', 'New York', 'Bruxelles', 'Bamako', 'Madrid'];

        foreach ($travelers as $traveler) {
            $nbTrips = rand(1, 3);

            for ($i = 0; $i < $nbTrips; $i++) {
                $departureCity = fake()->randomElement($cities);
                do {
                    $destinationCity = fake()->randomElement($cities);
                } while ($destinationCity === $departureCity);

                $departureCountry = fake()->countryCode();

                $trip = Trip::create([
                    'user_id'       => $traveler->id,
                    'departure'     => $departureCity . ', ' . $departureCountry,
                    'destination'   => $destinationCity . ', ' . fake()->countryCode(),
                    'date'          => now()->addDays(rand(5, 60)),
                    'capacity'      => fake()->numberBetween(5000, 50000),
                    'status'        => TripStatusEnum::ACTIVE->value,
                    'type_trip'     => TripTypeEnum::STANDARD->value,
                    'flight_number' => 'FL' . rand(100, 999),
                    'price_per_kg'  => fake()->numberBetween(500, 2500),
                    'currency'      => \App\Enums\CurrencyEnum::forCountry($departureCountry)->value,
                ]);

                $trip->locations()->createMany([
                    [
                        'city'        => $departureCity,
                        'latitude'    => fake()->latitude(),
                        'longitude'   => fake()->longitude(),
                        'position'    => LocationPositionEnum::DEPART->value,
                        'type'        => LocationTypeEnum::VILLE->value,
                        'order_index' => 0,
                    ],
                    [
                        'city'        => $destinationCity,
                        'latitude'    => fake()->latitude(),
                        'longitude'   => fake()->longitude(),
                        'position'    => LocationPositionEnum::ARRIVEE->value,
                        'type'        => LocationTypeEnum::VILLE->value,
                        'order_index' => 1,
                    ],
                ]);
            }
        }

        $this->command->info('✔ TripSeeder terminé avec succès.');
    }
}
