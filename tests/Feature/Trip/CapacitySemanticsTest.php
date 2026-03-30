<?php

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createBookingItemForTrip(
    Trip $trip,
    User $sender,
    BookingStatusEnum $status,
    float $kgReserved,
    ?\Illuminate\Support\Carbon $paymentExpiresAt = null
): Booking {
    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => $status,
        'payment_expires_at' => $paymentExpiresAt,
    ]);

    $luggage = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => $kgReserved,
        'price' => 50,
    ]);

    return $booking;
}

beforeEach(function () {
    $this->traveler = User::factory()->create([
        'role' => UserRoleEnum::TRAVELER,
    ]);

    $this->sender = User::factory()->create([
        'role' => UserRoleEnum::SENDER,
    ]);

    $this->trip = Trip::factory()->for($this->traveler)->create([
        'capacity' => 50,
    ]);
});

it('compte les bookings confirmés dans les kg réservés', function () {
    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        kgReserved: 12
    );

    expect($this->trip->fresh()->kgReserved())->toBe(12.0);
});

it('compte les bookings en paiement non expirés dans les kg réservés', function () {
    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 8,
        paymentExpiresAt: now()->addMinutes(10)
    );

    expect($this->trip->fresh()->kgReserved())->toBe(8.0);
});

it('ne compte pas les bookings en paiement expirés dans les kg réservés', function () {
    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 9,
        paymentExpiresAt: now()->subMinutes(10)
    );

    expect($this->trip->fresh()->kgReserved())->toBe(0.0);
});

it('additionne les bookings confirmés et en paiement non expirés', function () {
    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        kgReserved: 10
    );

    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 7,
        paymentExpiresAt: now()->addMinutes(15)
    );

    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 5,
        paymentExpiresAt: now()->subMinutes(15)
    );

    expect($this->trip->fresh()->kgReserved())->toBe(17.0)
        ->and($this->trip->fresh()->kgDisponible())->toBe(33.0);
});

it('canAcceptKg tient compte des bookings confirmés et en paiement non expirés', function () {
    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        kgReserved: 30
    );

    createBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 15,
        paymentExpiresAt: now()->addMinutes(10)
    );

    expect($this->trip->fresh()->canAcceptKg(4))->toBeTrue()
        ->and($this->trip->fresh()->canAcceptKg(6))->toBeFalse();
});
