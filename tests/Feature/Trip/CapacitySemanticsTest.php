<?php

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Enums\TripStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createCapacityBookingItemForTrip(
    Trip $trip,
    User $sender,
    BookingStatusEnum $status,
    float $kgReserved,
    ?Carbon $paymentExpiresAt = null
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
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        kgReserved: 12
    );

    expect($this->trip->fresh()->kgReserved())->toBe(12.0);
});

it('compte les bookings en paiement non expirés dans les kg réservés', function () {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 8,
        paymentExpiresAt: now()->addMinutes(10)
    );

    expect($this->trip->fresh()->kgReserved())->toBe(8.0);
});

it('ne compte pas les bookings en paiement expirés dans les kg réservés', function () {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 9,
        paymentExpiresAt: now()->subMinutes(10)
    );

    expect($this->trip->fresh()->kgReserved())->toBe(0.0);
});

it('additionne les bookings confirmés et en paiement non expirés', function () {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        kgReserved: 10
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 7,
        paymentExpiresAt: now()->addMinutes(15)
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 5,
        paymentExpiresAt: now()->subMinutes(15)
    );

    $trip = $this->trip->fresh();

    expect($trip->kgReserved())->toBe(17.0)
        ->and($trip->kgDisponible())->toBe(33.0);
});

it('canAcceptKg tient compte des bookings confirmés et en paiement non expirés', function () {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        kgReserved: 30
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        kgReserved: 15,
        paymentExpiresAt: now()->addMinutes(10)
    );

    $trip = $this->trip->fresh();

    expect($trip->canAcceptKg(4))->toBeTrue()
        ->and($trip->canAcceptKg(6))->toBeFalse();
});

it('ne considère pas un trajet terminé comme réservable', function () {
    expect(TripStatusEnum::COMPLETED->isReservable())->toBeFalse();
});

it('considère les trajets actifs et pending comme réservables', function () {
    expect(TripStatusEnum::ACTIVE->isReservable())->toBeTrue()
        ->and(TripStatusEnum::PENDING->isReservable())->toBeTrue();
});
