<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Models\User;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Luggage;
use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $expediteur = User::factory()->create([
        'role' => UserRoleEnum::SENDER,
    ]);

    Sanctum::actingAs($expediteur);
    test()->expediteur = $expediteur;
});

it('retourne la liste des bookings (index)', function () {
    Booking::factory()->count(3)->create(['user_id' => $this->expediteur->id]);

    $response = $this->getJson('/api/v1/bookings');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});
test('le rôle est bien casté en enum', function () {
    $user = User::factory()->create([
        'role' => UserRoleEnum::SENDER->value,
    ]);

    expect($user->role)->toBeInstanceOf(UserRoleEnum::class);
});

it('affiche une réservation spécifique (show)', function () {
    $trip = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => test()->expediteur->id,
        'trip_id' => $trip->id,
    ]);

    $response = $this->getJson("/api/v1/bookings/{$booking->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $booking->id);
});

it('crée une réservation (store)', function () {
    $expediteur = User::factory()->create(['role' => UserRoleEnum::SENDER]);
    $this->actingAs($expediteur);

    $trip = Trip::factory()->create();
    $luggage = Luggage::factory()->create([
        'user_id' => $expediteur->id,
        'status' => \App\Enums\LuggageStatusEnum::EN_ATTENTE,
    ]);

    $data = [
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 2.5,
                'price' => 100.0,
            ]
        ]
    ];

    $response = $this->postJson('/api/v1/bookings', $data);

    $response->assertCreated()
        ->assertJsonPath('data.status', BookingStatusEnum::EN_ATTENTE->value)
        ->assertJsonPath('data.booking_items.0.luggage.id', $luggage->id);
});





it('met à jour une réservation (update)', function () {
    $trip = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => test()->expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_ATTENTE,
    ]);

    $data = [
        'status' => BookingStatusEnum::ANNULE->value,
    ];

    $response = $this->putJson("/api/v1/bookings/{$booking->id}", $data);

    $response->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::ANNULE->value);
});

it('supprime une réservation (destroy)', function () {
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => test()->expediteur->id,
        'trip_id' => $trip->id,
    ]);

    $response = $this->deleteJson("/api/v1/bookings/{$booking->id}");

    $response->assertOk()
        ->assertJson([
            'message' => 'Réservation supprimée.',
        ]);

    // ✅ Vérifie que le booking est soft-deleted
    $this->assertSoftDeleted('bookings', [
        'id' => $booking->id,
    ]);
});
