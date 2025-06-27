<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use App\Enums\TripTypeEnum;
use App\Enums\TripStatusEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $travelers = User::where('role', \App\Enums\UserRoleEnum::TRAVELER->value)->get();

        $cities = ['Dakar', 'Paris', 'Abidjan', 'Casablanca', 'New York', 'Bruxelles', 'Bamako', 'Madrid'];

        if (count($cities) < 2) {
            $this->command->error('⚠ Liste de villes trop courte.');
            return;
        }

        foreach ($travelers as $traveler) {
            $nbTrips = rand(1, 3); // Un voyageur peut proposer 1 à 3 trajets

            for ($i = 0; $i < $nbTrips; $i++) {
                $departure   = fake()->randomElement($cities);
                $destOptions = array_diff($cities, [$departure]);

                $destination = fake()->randomElement($destOptions);

                Trip::create([
                    'user_id'       => $traveler->id,
                    'departure'     => $departure,
                    'destination'   => $destination,
                    'date'          => Carbon::now()->addDays(rand(2, 30)),
                    'capacity'      => rand(5, 25),
                    'status'        => TripStatusEnum::ACTIVE->value,
                    'type_trip'     => fake()->randomElement(TripTypeEnum::cases())->value,
                    'flight_number' => fake()->optional(60)->regexify('[A-Z]{2}[0-9]{3,4}'),
                ]);
            }
        }

        $this->command->info('✔ TripSeeder terminé avec succès.');
    }
}
