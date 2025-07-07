<?php

use App\Models\Location;
use App\Models\Trip;
use App\Models\User;
use App\Enums\LocationTypeEnum;
use App\Enums\LocationPositionEnum;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin   = User::factory()->admin()->create();
    $this->trusted = User::factory()->traveler()->create();
    $this->basic   = User::factory()->expeditor()->create();

    $this->trip = Trip::factory()->for($this->trusted)->create();
});

it('liste toutes les locations', function () {
    $this->actingAs($this->trusted);

    Location::factory()->count(3)->create([
        'trip_id' => $this->trip->id,
    ]);

    $response = $this->getJson('/api/v1/locations');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'latitude', 'longitude', 'city']]]);
});

it('affiche une location spécifique', function () {
    $this->actingAs($this->trusted);

    $location = Location::factory()->create([
        'trip_id' => $this->trip->id,
    ]);

    $response = $this->getJson("/api/v1/locations/{$location->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $location->id);
});

it('crée une location si autorisé (admin ou trusted)', function () {
    $this->actingAs($this->admin);

    $payload = [
        'trip_id'     => $this->trip->id,
        'latitude'    => 14.6928,
        'longitude'   => -17.4467,
        'country'     => 'Sénégal',
        'city'        => 'Dakar',
        'postcode'    => '10000',
        'address'     => 'Rue X',
        'type'        => LocationTypeEnum::VILLE->value,
        'position'    => LocationPositionEnum::DEPART->value,
        'order_index' => 1,
    ];

    $response = $this->postJson('/api/v1/locations', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.city', 'Dakar');
});

it('rejette la création de location pour un utilisateur non autorisé', function () {
    $this->actingAs($this->basic);

    $payload = [
        'trip_id'     => $this->trip->id,
        'latitude'    => 33.9716,
        'longitude'   => -6.8498,
        'country'     => 'Maroc',
        'city'        => 'Rabat',
        'type'        => LocationTypeEnum::VILLE->value,
        'position'    => LocationPositionEnum::DEPART->value,
        'order_index' => 1,
    ];

    $response = $this->postJson('/api/v1/locations', $payload);

    $response->assertForbidden();
});
