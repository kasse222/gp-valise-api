<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\PickupLocation;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->traveler = User::factory()->traveler()->verified()->create();
    $this->sender   = User::factory()->sender()->verified()->create();
    $this->trip     = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $this->booking  = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
        'status'  => BookingStatusEnum::CONFIRMEE,
    ]);
});

it('traveler peut définir un point de dépôt', function (): void {
    Sanctum::actingAs($this->traveler);

    $this->postJson("/api/v1/bookings/{$this->booking->id}/pickup-location", [
        'latitude'              => 33.5731,
        'longitude'             => -7.5898,
        'approximate_latitude'  => 33.5780,
        'approximate_longitude' => -7.5850,
        'address'               => '50 rue Ouled Ziane, Casablanca',
        'city'                  => 'Casablanca',
        'instructions'          => 'Sonner à l\'interphone numéro 3.',
    ])->assertCreated()
        ->assertJsonPath('data.city', 'Casablanca')
        ->assertJsonPath('data.revealed', true);
});

it('sender ne peut pas définir un point de dépôt', function (): void {
    Sanctum::actingAs($this->sender);

    $this->postJson("/api/v1/bookings/{$this->booking->id}/pickup-location", [
        'latitude'              => 33.5731,
        'longitude'             => -7.5898,
        'approximate_latitude'  => 33.5780,
        'approximate_longitude' => -7.5850,
        'address'               => '50 rue Ouled Ziane, Casablanca',
        'city'                  => 'Casablanca',
    ])->assertForbidden();
});

it('sender voit les coordonnées exactes si booking CONFIRMEE', function (): void {
    Sanctum::actingAs($this->sender);

    PickupLocation::factory()->create([
        'booking_id'            => $this->booking->id,
        'latitude'              => 33.5731,
        'longitude'             => -7.5898,
        'approximate_latitude'  => 33.5780,
        'approximate_longitude' => -7.5850,
        'address'               => '50 rue Ouled Ziane, Casablanca',
        'city'                  => 'Casablanca',
    ]);

    $this->getJson("/api/v1/bookings/{$this->booking->id}/pickup-location")
        ->assertOk()
        ->assertJsonPath('data.revealed', true)
        ->assertJsonPath('data.address', '50 rue Ouled Ziane, Casablanca')
        ->assertJsonPath('data.latitude', 33.5731);
});

it('sender ne voit pas les coordonnées exactes si booking PENDING_APPROVAL', function (): void {
    $this->booking->update(['status' => BookingStatusEnum::PENDING_APPROVAL]);

    Sanctum::actingAs($this->sender);

    PickupLocation::factory()->create([
        'booking_id'            => $this->booking->id,
        'latitude'              => 33.5731,
        'longitude'             => -7.5898,
        'approximate_latitude'  => 33.5780,
        'approximate_longitude' => -7.5850,
        'address'               => '50 rue Ouled Ziane, Casablanca',
        'city'                  => 'Casablanca',
    ]);

    $this->getJson("/api/v1/bookings/{$this->booking->id}/pickup-location")
        ->assertOk()
        ->assertJsonPath('data.revealed', false)
        ->assertJsonPath('data.address', null)
        ->assertJsonPath('data.latitude', null)
        ->assertJsonPath('data.approximate_latitude', 33.5780);
});

it('retourne null si aucun point de dépôt défini', function (): void {
    Sanctum::actingAs($this->sender);

    $this->getJson("/api/v1/bookings/{$this->booking->id}/pickup-location")
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('autre user ne peut pas voir le point de dépôt', function (): void {
    $autreUser = User::factory()->sender()->create();
    Sanctum::actingAs($autreUser);

    $this->getJson("/api/v1/bookings/{$this->booking->id}/pickup-location")
        ->assertForbidden();
});
