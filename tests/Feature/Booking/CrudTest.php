<?php

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

beforeEach(function () {
    $expediteur = User::factory()->create([
        'role' => UserRoleEnum::SENDER->value,
    ]);

    Sanctum::actingAs($expediteur);
    test()->expediteur = $expediteur;
});

it('retourne la liste des bookings (index)', function () {
    Booking::factory()->count(3)->create([
        'user_id' => test()->expediteur->id,
    ]);

    $response = $this->getJson('/api/v1/bookings');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('le rôle est bien casté en enum', function () {
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

    $luggage = Luggage::factory()->create([
        'user_id' => test()->expediteur->id,
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
    ]);

    $response = $this->getJson("/api/v1/bookings/{$booking->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $booking->id)
        ->assertJsonPath('data.items.0.id', $item->id)
        ->assertJsonPath('data.items.0.luggage.id', $luggage->id);
});

it('crée une réservation (store)', function () {
    $traveler = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $traveler->id,
    ]);

    $luggage = Luggage::factory()->create([
        'user_id' => test()->expediteur->id,
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $data = [
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 2.5,
                'price' => 100.0,
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/bookings', $data);

    $response->assertCreated()
        ->assertJsonPath('data.status', BookingStatusEnum::EN_PAIEMENT->value)
        ->assertJsonPath('data.items.0.luggage.id', $luggage->id)
        ->assertJsonPath('data.payment_expires_at', fn($value) => ! is_null($value));
});

it('supprime une réservation (destroy)', function () {
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => test()->expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
    ]);

    $response = $this->deleteJson("/api/v1/bookings/{$booking->id}");

    $response->assertOk()
        ->assertJson([
            'message' => 'Réservation supprimée.',
        ]);

    $this->assertSoftDeleted('bookings', [
        'id' => $booking->id,
    ]);
});
