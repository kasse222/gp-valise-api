<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\User;
use App\Enums\TripTypeEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $travelers = User::where('role', 2)->get(); // TRAVELER

        $cities = ['Dakar', 'Paris', 'Abidjan', 'Casablanca', 'New York', 'Bruxelles', 'Bamako', 'Madrid'];

        foreach ($travelers as $traveler) {
            for ($i = 0; $i < 2; $i++) {
                $departure    = fake()->randomElement($cities);
                $destination  = fake()->randomElement(array_diff($cities, [$departure]));

                Trip::create([
                    'user_id'      => $traveler->id,
                    'departure'    => $departure,
                    'destination'  => $destination,
                    'date'         => Carbon::now()->addDays(rand(2, 30)),
                    'capacity'     => rand(5, 25), // en kg
                    'status'       => 'actif',
                    'type_trip'    => fake()->randomElement(TripTypeEnum::values()),
                    'flight_number' => fake()->optional()->regexify('[A-Z]{2}[0-9]{3,4}'),
                ]);
            }
        }
    }
}
