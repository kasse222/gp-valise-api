<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
    ]);
    actingAs($this->user);
    $this->trip = Trip::factory()->create(['user_id' => $this->user->id]);
});

/** 
test('index retourne les réservations de l’utilisateur connecté', function () {
    Booking::factory()->count(2)->create(['trip_id' => $this->trip->id]);

    $response = getJson('/api/v1/bookings');
    $response->assertStatus(200)->assertJsonCount(2);
});


test('store crée une réservation', function () {
    $luggage = \App\Models\Luggage::factory()->create(['status' => 'en_attente']);

    $payload = [
        'trip_id' => $this->trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 10,
                'price' => 55.00,
            ],
        ],
    ];

    $response = postJson('/api/v1/bookings', $payload);
    $response->assertCreated();
    expect(Booking::count())->toBe(1);
});

test('show retourne une réservation spécifique', function () {
    $booking = Booking::factory()->create(['trip_id' => $this->trip->id]);

    $response = getJson("/api/v1/bookings/{$booking->id}");
    $response->assertOk()->assertJsonFragment(['id' => $booking->id]);
});

test('update modifie le statut d’une réservation', function () {
    $voyageur = User::factory()->create(); // Le voyageur
    $trip = Trip::factory()->create(['user_id' => $voyageur->id]);
    $booking = Booking::factory()->create([
        'trip_id' => $trip->id,
        'status' => 'en_attente',
    ]);
    actingAs($voyageur); // Simule la connexion du bon user

    $response = putJson("/api/v1/bookings/{$booking->id}", ['status' => 'accepte']);

    $response->assertOk()->assertJsonPath('booking.status', 'accepte');
});

test('on ne peut pas changer une réservation terminée', function () {
    $voyageur = User::factory()->create();
    $trip = Trip::factory()->create(['user_id' => $voyageur->id]);
    $booking = Booking::factory()->create([
        'trip_id' => $trip->id,
        'status' => 'termine',
    ]);

    actingAs($voyageur);

    $response = putJson("/api/v1/bookings/{$booking->id}", ['status' => 'annule']);
    $response->assertStatus(403);
});


test('on ne peut pas mettre un statut invalide', function () {
    $luggage = Luggage::factory()->create();
    $trip = Trip::factory()->create(['user_id' => $this->user->id]);
    $booking = Booking::factory()->create([
        'trip_id' => $trip->id,

    ]);

    $response = putJson("/api/v1/bookings/{$booking->id}", [
        'status' => 'invalide',
    ]);

    $response->assertStatus(422);
});



test('destroy supprime une réservation', function () {
    $booking = Booking::factory()->create(['trip_id' => $this->trip->id]);

    $response = deleteJson("/api/v1/bookings/{$booking->id}");
    $response->assertOk();

    expect(Booking::find($booking->id))->toBeNull();
});
 */
