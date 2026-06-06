<?php

declare(strict_types=1);

use App\Enums\TripTypeEnum;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->traveler()->create();
    actingAs($this->user);
});

it('liste les trajets', function (): void {
    Trip::factory()->count(3)->create(['user_id' => $this->user->id]);

    getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('affiche un trajet spécifique', function (): void {
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);

    getJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $trip->id]);
});

it('crée un trajet avec des données valides', function (): void {
    $data = [
        'departure'     => 'Paris, FR',
        'destination'   => 'Dakar, SN',
        'date'          => now()->addDays(3)->toDateString(),
        'flight_number' => 'AF123',
        'capacity'      => 40000,       // ← grammes : 40kg
        'price_per_kg'  => 2050,        // ← centimes : 20.50€/kg
        'type_trip'     => TripTypeEnum::STANDARD->value,
    ];

    postJson('/api/v1/trips', $data)
        ->assertCreated()
        ->assertJsonFragment([
            'departure'   => 'Paris, FR',
            'destination' => 'Dakar, SN',
            'capacity'    => 40000,
        ]);
});

it('met à jour un trajet avec autorisation', function (): void {
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);

    putJson("/api/v1/trips/{$trip->id}", ['price_per_kg' => 2500]) // ← 25.00€ = 2500 centimes
        ->assertOk()
        ->assertJsonFragment(['price_per_kg' => 2500]);
});

it('rejette la mise à jour d\'un trajet non autorisé', function (): void {
    $trip = Trip::factory()->create();

    putJson("/api/v1/trips/{$trip->id}", ['price_per_kg' => 3000])
        ->assertForbidden();
});

it('supprime un trajet avec succès', function (): void {
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);

    deleteJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonFragment(['message' => 'Trajet supprimé avec succès.']);

    $this->assertSoftDeleted($trip);
});

it('rejette la suppression d\'un trajet d\'un autre utilisateur', function (): void {
    $trip = Trip::factory()->create();

    deleteJson("/api/v1/trips/{$trip->id}")
        ->assertForbidden();
});

it('crée un trajet avec pickup location', function (): void {
    $data = [
        'departure'     => 'Paris, FR',
        'destination'   => 'Dakar, SN',
        'date'          => now()->addDays(3)->toDateString(),
        'capacity'      => 40000,
        'price_per_kg'  => 2050,
        'type_trip'     => TripTypeEnum::STANDARD->value,

        'pickup_address'               => '12 rue de la Paix',
        'pickup_city'                  => 'Paris',
        'pickup_latitude'              => 48.8566,
        'pickup_longitude'             => 2.3522,
        'pickup_approx_latitude'       => 48.85,
        'pickup_approx_longitude'      => 2.35,
        'pickup_instructions'          => 'Devant l\'entrée principale',
    ];

    postJson('/api/v1/trips', $data)
        ->assertCreated()
        ->assertJsonPath('data.pickup_location.city', 'Paris')
        ->assertJsonPath('data.pickup_location.revealed', true)
        ->assertJsonPath('data.pickup_location.address', '12 rue de la Paix');
});
