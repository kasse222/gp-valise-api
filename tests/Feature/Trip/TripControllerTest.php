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
    $this->user = User::factory()->traveler()->withKyc()->create();
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
        'currency'      => 'EUR',
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
        'currency'      => 'EUR',
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

it('bloque la création de trajet si traveler sans KYC', function (): void {
    $user = User::factory()->traveler()->create(['kyc_passed_at' => null]);
    actingAs($user);

    postJson('/api/v1/trips', [
        'departure'    => 'Paris, FR',
        'destination'  => 'Dakar, SN',
        'date'         => now()->addDays(3)->toDateString(),
        'capacity'     => 40000,
        'price_per_kg' => 2050,
        'currency'     => 'EUR',
        'type_trip'    => TripTypeEnum::STANDARD->value,
    ])->assertStatus(422)
        ->assertJsonPath('errors.kyc.0', "Vous devez compléter votre vérification d'identité (KYC) avant de publier un trajet.");
});

it('filtre les trajets par departure', function (): void {
    Trip::factory()->create(['user_id' => $this->user->id, 'departure' => 'Paris, FR', 'destination' => 'Dakar, SN']);
    Trip::factory()->create(['user_id' => $this->user->id, 'departure' => 'Casablanca, MA', 'destination' => 'Paris, FR']);

    getJson('/api/v1/trips?departure=Paris')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filtre les trajets par destination', function (): void {
    Trip::factory()->create(['user_id' => $this->user->id, 'departure' => 'Paris, FR', 'destination' => 'Dakar, SN']);
    Trip::factory()->create(['user_id' => $this->user->id, 'departure' => 'Paris, FR', 'destination' => 'Casablanca, MA']);

    getJson('/api/v1/trips?destination=Dakar')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filtre les trajets par price_max', function (): void {
    Trip::factory()->create(['user_id' => $this->user->id, 'price_per_kg' => 1000]);
    Trip::factory()->create(['user_id' => $this->user->id, 'price_per_kg' => 3000]);

    getJson('/api/v1/trips?price_max=1500')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
