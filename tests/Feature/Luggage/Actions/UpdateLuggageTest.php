<?php

use App\Models\User;
use App\Actions\Luggage\CreateLuggage;
use App\Enums\LuggageStatusEnum;
use App\Models\Luggage;
use App\Models\Trip;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);


it('crée une valise avec les données valides', function () {
    $user = User::factory()->sender()->create();
    $trip = Trip::factory()->for($user)->create();

    $data = [
        'weight_kg'     => 25,
        'length_cm'     => 60,
        'width_cm'      => 40,
        'height_cm'     => 30,
        'pickup_city'   => 'Toulouse',
        'delivery_city' => 'Bamako',
        'pickup_date'   => now()->addDays(2)->toDateString(),
        'delivery_date' => now()->addDays(5)->toDateString(),
        'trip_id'       => $trip->id,
    ];

    $luggage = CreateLuggage::execute($user, $data);

    expect($luggage)
        ->user_id->toBe($user->id)
        ->trip_id->toBe($trip->id) // 
        ->status->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->pickup_city->toBe('Toulouse')
        ->delivery_city->toBe('Bamako')
        ->trip_id->toBe($trip->id);
});


it('calcule automatiquement le volume du colis à la sauvegarde', function () {
    $luggage = Luggage::factory()->create([
        'length_cm' => 10,
        'width_cm' => 20,
        'height_cm' => 30,
    ]);

    expect($luggage->fresh()->volume_cm3)->toBe(6000.0);
});
