<?php

use App\Models\User;
use App\Models\Luggage;
use App\Enums\UserRoleEnum;
use App\Models\Trip;

use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    $this->user = User::factory()->sender()->create();
    $this->luggage = Luggage::factory()->create([
        'user_id' => $this->user->id,
    ]);

    actingAs($this->user);
});

it('liste les valises de l’utilisateur connecté', function () {
    $response = getJson(route('api.v1.luggages.index'));

    $response->assertOk()
        ->assertJsonStructure(['data' => [[
            'id',
            'tracking_id',
            'weight_kg',
        ]]]);
});

it('crée une nouvelle valise', function () {

    $user = User::factory()->sender()->create();
    $trip = Trip::factory()->create(['user_id' => $user->id]);

    actingAs($user);

    $data = [
        'trip_id'             => $trip->id,
        'weight_kg'           => 20,
        'length_cm'           => 50,
        'width_cm'            => 30,
        'height_cm'           => 20,
        'pickup_city'         => 'Paris',
        'delivery_city'       => 'Dakar',
        'pickup_date'         => now()->addDays(3)->toDateString(),
        'delivery_date'       => now()->addDays(7)->toDateString(),
    ];
    $response = postJson(route('api.v1.luggages.store'), $data);

    if ($response->status() === 403) {
        $this->fail('403 reçu → vérifie la LuggagePolicy ou le rôle de l’utilisateur.');
    }

    $response->assertCreated()
        ->assertJsonPath('pickup_city', 'Paris');
});

it('affiche une valise si utilisateur autorisé', function () {
    $response = getJson(route('api.v1.luggages.show', $this->luggage));

    $response->assertOk()
        ->assertJsonPath('data.id', $this->luggage->id);
});

it('rejette l’accès à une valise si utilisateur non autorisé', function () {
    $other = User::factory()->sender()->create();
    actingAs($other);

    $luggage = Luggage::factory()->create();

    $response = getJson(route('api.v1.luggages.show', $luggage));

    $response->assertForbidden();
});

it('met à jour une valise', function () {
    $user = User::factory()->sender()->create();
    $luggage = Luggage::factory()->create(['user_id' => $user->id]);

    actingAs($user);

    $data = ['weight_kg' => 30];

    $response = putJson(route('api.v1.luggages.update', $luggage), $data);

    $response->assertOk()
        ->assertJsonPath('data.weight_kg', 30);
});

it('supprime une valise', function () {
    $luggage = Luggage::factory()->create(['user_id' => $this->user->id]);

    $response = deleteJson(route('api.v1.luggages.destroy', $luggage));

    $response->assertOk()
        ->assertJsonPath('message', 'Valise supprimée avec succès.');
});
