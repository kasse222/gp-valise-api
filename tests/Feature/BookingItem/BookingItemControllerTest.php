<?php

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->expeditor = User::factory()->sender()->verified()->create();

    $this->trip = Trip::factory()->create();

    $this->luggage = Luggage::factory()->create([
        'user_id' => $this->expeditor->id,
        'weight_kg' => 20,
    ]);

    $this->booking = Booking::factory()->create([
        'user_id' => $this->expeditor->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::ACCEPTE,
    ]);

    actingAs($this->expeditor);
});

it('liste les booking items d’un booking', function () {
    BookingItem::factory()->count(3)->create([
        'booking_id' => $this->booking->id,
        'trip_id' => $this->trip->id,
    ]);

    $response = $this->getJson(route('api.v1.bookings.items.index', $this->booking));

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('crée un booking item avec des données valides', function () {
    $data = [
        'kg_reserved' => 10,
        'price'       => 50,
        'luggage_id'  => $this->luggage->id,
    ];

    $response = $this->postJson(
        route('api.v1.bookings.items.store', $this->booking),
        $data
    );

    $response->assertCreated()
        ->assertJsonPath('data.kg_reserved', 10);
});

it('refuse la création si utilisateur non autorisé', function () {
    $user = User::factory()->traveler()->create();
    actingAs($user);

    $data = [
        'booking_id'  => $this->booking->id,
        'luggage_id'  => $this->luggage->id,
        'trip_id'     => $this->trip->id,
        'kg_reserved' => 5,
        'price'       => 30,
    ];

    $response = $this->postJson(
        route('api.v1.bookings.items.store', $this->booking),
        $data
    );

    $response->assertForbidden();
});

it('met à jour un booking item existant', function () {
    $item = BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'trip_id' => $this->trip->id,
        'kg_reserved' => 5,
        'price' => 30,
    ]);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'kg_reserved' => 15,
        'price'       => 60,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.kg_reserved', 15)
        ->assertJsonPath('data.price', 60);
});

it('refuse de modifier un booking item si la réservation est finalisée', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->expeditor->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::TERMINE,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $this->trip->id,
    ]);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'kg_reserved' => 20,
        'price'       => 80,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['booking_item']);
});

it('refuse de modifier booking_id, trip_id ou luggage_id', function () {
    $item = BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'trip_id' => $this->trip->id,
        'luggage_id' => $this->luggage->id,
    ]);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'booking_id' => 999,
        'trip_id' => 999,
        'luggage_id' => 999,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'booking_id',
            'trip_id',
            'luggage_id',
        ]);
});

it('refuse la modification par un autre utilisateur', function () {
    $owner = User::factory()->sender()->verified()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $owner->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::ACCEPTE,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'kg_reserved' => 5,
        'price' => 30,
    ]);

    $otherUser = User::factory()->sender()->verified()->create();
    actingAs($otherUser);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'kg_reserved' => 7,
    ]);

    $response->assertForbidden();
});

it('autorise la modification des champs autorisés au propriétaire', function () {
    $item = BookingItem::factory()->create([
        'booking_id' => $this->booking->id,
        'trip_id' => $this->trip->id,
        'kg_reserved' => 5,
        'price' => 30,
    ]);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'kg_reserved' => 12.5,
        'price' => 75,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.kg_reserved', 12.5)
        ->assertJsonPath('data.price', 75);
});

it('supprime un booking item existant', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->expeditor->id,
        'trip_id' => $this->trip->id,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $this->trip->id,
    ]);

    $response = $this->deleteJson(route('api.v1.booking_items.destroy', $item));

    $response->assertOk();
    $this->assertDatabaseMissing('booking_items', ['id' => $item->id]);
});

it('refuse la suppression si l’utilisateur n’est pas le propriétaire', function () {
    $otherUser = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $otherUser->id,
        'trip_id' => $this->trip->id,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $this->trip->id,
    ]);

    $response = $this->deleteJson(route('api.v1.booking_items.destroy', $item));

    $response->assertForbidden();
});
