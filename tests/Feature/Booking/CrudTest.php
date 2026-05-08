<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $expediteur = User::factory()->create(['role' => UserRoleEnum::SENDER->value]);
    Sanctum::actingAs($expediteur);
    test()->expediteur = $expediteur;
});

it('retourne la liste des bookings (index)', function (): void {
    Booking::factory()->count(3)->create(['user_id' => test()->expediteur->id]);

    $this->getJson('/api/v1/bookings')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('le rôle est bien casté en enum', function (): void {
    $user = User::factory()->create(['role' => UserRoleEnum::SENDER->value]);

    expect($user->role)->toBeInstanceOf(UserRoleEnum::class);
});

it('affiche une réservation spécifique (show)', function (): void {
    $trip    = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => test()->expediteur->id,
        'trip_id' => $trip->id,
    ]);
    $luggage = Luggage::factory()->create([
        'user_id' => test()->expediteur->id,
        'status'  => LuggageStatusEnum::EN_ATTENTE,
    ]);
    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id'    => $trip->id,
        'luggage_id' => $luggage->id,
    ]);

    $this->getJson("/api/v1/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $booking->id)
        ->assertJsonPath('data.items.0.id', $item->id)
        ->assertJsonPath('data.items.0.luggage.id', $luggage->id);
});

it('crée une réservation (store)', function (): void {
    $traveler = User::factory()->traveler()->verified()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);
    $luggage  = Luggage::factory()->create([
        'user_id' => test()->expediteur->id,
        'status'  => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $data = [
        'trip_id' => $trip->id,
        'items'   => [
            [
                'luggage_id'  => $luggage->id,
                'kg_reserved' => 2500,  // ← grammes : 2.5kg
                'price'       => 10000, // ← centimes : 100.00€
            ],
        ],
    ];

    $this->postJson('/api/v1/bookings', $data)
        ->assertCreated()
        ->assertJsonPath('data.status', BookingStatusEnum::EN_PAIEMENT->value)
        ->assertJsonPath('data.items.0.luggage.id', $luggage->id)
        ->assertJsonPath('data.payment_expires_at', fn($value) => ! is_null($value));
});

it('supprime une réservation (destroy)', function (): void {
    $trip    = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => test()->expediteur->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_PAIEMENT,
    ]);

    $this->deleteJson("/api/v1/bookings/{$booking->id}")
        ->assertOk()
        ->assertJson(['message' => 'Réservation supprimée.']);

    $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
});

it('refuse la création de réservation à un TRAVELER (403)', function (): void {
    Sanctum::actingAs(User::factory()->traveler()->create());

    $this->postJson('/api/v1/bookings', [])->assertForbidden();
});

it('autorise la création de réservation à un SENDER (201)', function (): void {
    $sender   = User::factory()->sender()->create();
    $traveler = User::factory()->traveler()->verified()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);
    $luggage  = Luggage::factory()->create([
        'user_id' => $sender->id,
        'status'  => LuggageStatusEnum::EN_ATTENTE,
    ]);

    Sanctum::actingAs($sender);

    $this->postJson('/api/v1/bookings', [
        'trip_id' => $trip->id,
        'items'   => [
            [
                'luggage_id'  => $luggage->id,
                'kg_reserved' => 2000,  // ← grammes : 2kg
                'price'       => 5000,  // ← centimes : 50.00€
            ],
        ],
    ])->assertCreated();
});

it('refuse la création de réservation à un ADMIN (403)', function (): void {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/bookings', [])->assertForbidden();
});
