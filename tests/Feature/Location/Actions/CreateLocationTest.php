<?php

use App\Actions\Location\CreateLocation;
use App\Enums\LocationPositionEnum;
use App\Enums\LocationTypeEnum;
use App\Models\Location;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('crée une location avec des données valides', function () {
    $user = User::factory()->traveler()->create();
    $trip = Trip::factory()->for($user)->create();

    $data = [
        'trip_id'    => $trip->id,
        'country'    => 'Sénégal',
        'city'       => 'Dakar',
        'postcode'   => '10000',
        'address'    => 'Rue X',
        'latitude'   => 14.6928,
        'longitude'  => -17.4467,
        'position'   => LocationPositionEnum::DEPART->value,
        'type'       => LocationTypeEnum::AEROPORT->value,
        'order_index' => 0,
    ];

    $location = CreateLocation::execute($data);

    expect($location)->toBeInstanceOf(Location::class)
        ->and($location->city)->toBe('Dakar')
        ->and($location->trip_id)->toBe($trip->id)
        ->and(Location::count())->toBe(1);
});
