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

    $trip = Trip::factory()->create(['user_id' => $user->id]);

    $data = [
        'trip_id'       => $trip->id,
        'weight_kg'     => 25,
        'length_cm'     => 60,
        'width_cm'      => 40,
        'height_cm'     => 30,
        'pickup_city'   => 'Toulouse',
        'delivery_city' => 'Bamako',
        'pickup_date'   => now()->addDays(2)->toDateString(),
        'delivery_date' => now()->addDays(5)->toDateString(),
    ];

    $luggage = CreateLuggage::execute($user, $data);

    expect($luggage)
        ->user_id->toBe($user->id)
        ->status->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->pickup_city->toBe('Toulouse')
        ->weight_kg->toEqual(25);
});
