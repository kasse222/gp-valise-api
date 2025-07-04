<?php

use App\Enums\TripTypeEnum;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

uses(RefreshDatabase::class);

use App\Enums\UserRoleEnum;

beforeEach(function () {
    $this->user = User::factory()->traveler()->create();

    actingAs($this->user);
});


it('liste les trajets', function () {
    Trip::factory()->count(3)->create(['user_id' => $this->user->id]);

    getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('affiche un trajet spécifique', function () {
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);

    getJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $trip->id]);
});

it('crée un trajet avec des données valides', function () {
    $data = [
        'departure'      => 'Paris, FR',
        'destination'    => 'Dakar, SN',
        'date'           => now()->addDays(3)->toDateString(),
        'flight_number'  => 'AF123',
        'capacity'       => 40,
        'price_per_kg'   => 20.50,
        'type_trip'      => TripTypeEnum::STANDARD->value, // ou 'standard' si string
    ];

    postJson('/api/v1/trips', $data)
        ->assertCreated()
        ->assertJsonFragment([
            'departure'   => 'Paris, FR',
            'destination' => 'Dakar, SN',
            'capacity'    => 40,
        ]);
});



it('met à jour un trajet avec autorisation', function () {
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);

    $payload = ['price_per_kg' => 25.00];

    putJson("/api/v1/trips/{$trip->id}", $payload)
        ->assertOk()
        ->assertJsonFragment(['price_per_kg' => 25.00]);
});

it('rejette la mise à jour d’un trajet non autorisé', function () {
    $trip = Trip::factory()->create(); // autre user

    putJson("/api/v1/trips/{$trip->id}", ['price_per_kg' => 30])
        ->assertForbidden();
});

it('supprime un trajet avec succès', function () {
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);

    deleteJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonFragment(['message' => 'Trajet supprimé avec succès.']);

    $this->assertSoftDeleted($trip);
});

it('rejette la suppression d’un trajet d’un autre utilisateur', function () {
    $trip = Trip::factory()->create(); // autre user

    deleteJson("/api/v1/trips/{$trip->id}")
        ->assertForbidden();
});
