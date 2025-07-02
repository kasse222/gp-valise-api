<?php

use App\Models\User;
use App\Actions\Luggage\CreateLuggage;
use App\Enums\LuggageStatusEnum;
use App\Models\Trip;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);


it('crée une valise avec les données valides', function () {
    $user = User::factory()->sender()->create();
    $trip = Trip::factory()->for($user)->create(); // ✅ Création du voyage

    $data = [
        'weight_kg'     => 25,
        'length_cm'     => 60,
        'width_cm'      => 40,
        'height_cm'     => 30,
        'pickup_city'   => 'Toulouse',
        'delivery_city' => 'Bamako',
        'pickup_date'   => now()->addDays(2)->toDateString(),
        'delivery_date' => now()->addDays(5)->toDateString(),
        'trip_id'       => $trip->id, // ✅ Important
    ];

    $luggage = CreateLuggage::execute($user, $data);

    expect($luggage)
        ->user_id->toBe($user->id)
        ->trip_id->toBe($trip->id) // 💡 on peut aussi tester ce lien
        ->status->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->pickup_city->toBe('Toulouse')
        ->delivery_city->toBe('Bamako')
        ->trip_id->toBe($trip->id);
});
