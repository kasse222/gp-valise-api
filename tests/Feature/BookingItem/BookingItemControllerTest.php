<?php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

// Setup commun à chaque test
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
    ]);

    actingAs($this->expeditor);
});

it('liste les booking items d’un booking', function () {
    BookingItem::factory()->count(3)->create(['booking_id' => $this->booking->id]);

    $response = $this->getJson(route('api.v1.bookings.items.index', $this->booking));

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('crée un booking item avec des données valides', function () {
    $data = [
        'kg_reserved' => 10,
        'price'       => 50,
        'luggage_id'  => $this->luggage->id, // ✅ manquant avant
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

    $response = $this->postJson(route('api.v1.bookings.items.store', $this->booking), $data);
    $response->assertForbidden();
});

it('met à jour un booking item existant', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::confirmed(), // ✅ statut non final
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'kg_reserved' => 15,
        'price'       => 60,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.kg_reserved', 15);
});


it('supprime un booking item existant', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->expeditor->id, // 🔐 clé : bien relier au user connecté
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->deleteJson(route('api.v1.booking_items.destroy', $item));

    $response->assertOk();
    $this->assertDatabaseMissing('booking_items', ['id' => $item->id]);
});

it('refuse de modifier un booking item si la réservation est finalisée', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::TERMINE, // statut finalisé
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
    ]);

    $response = $this->putJson(route('api.v1.booking_items.update', $item), [
        'kg_reserved' => 20,
        'price'       => 80,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['booking_item']);
});
it('refuse la suppression si l’utilisateur n’est pas le propriétaire', function () {
    $otherUser = User::factory()->create();

    $booking = Booking::factory()->create(['user_id' => $otherUser->id]);

    $item = BookingItem::factory()->create(['booking_id' => $booking->id]);

    $response = $this->deleteJson(route('api.v1.booking_items.destroy', $item));

    $response->assertForbidden();
});
