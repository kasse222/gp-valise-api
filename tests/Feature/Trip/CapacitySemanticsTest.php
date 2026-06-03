<?php

declare(strict_types=1);

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
    int $grams,
    ?Carbon $paymentExpiresAt = null
): Booking {
    $booking = Booking::factory()->create([
        'user_id'            => $sender->id,
        'trip_id'            => $trip->id,
        'status'             => $status,
        'payment_expires_at' => $paymentExpiresAt,
    ]);

    $luggage = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id'  => $booking->id,
        'trip_id'     => $trip->id,
        'luggage_id'  => $luggage->id,
        'kg_reserved' => $grams, // ← grammes
        'price'       => 5000,   // ← 50.00€ en centimes
    ]);

    return $booking;
}

beforeEach(function (): void {
    $this->traveler = User::factory()->create(['role' => UserRoleEnum::TRAVELER]);
    $this->sender   = User::factory()->create(['role' => UserRoleEnum::SENDER]);
    $this->trip     = Trip::factory()->for($this->traveler)->create([
        'capacity' => 50000, // ← 50kg en grammes
    ]);
});

it('compte les bookings confirmés dans les kg réservés', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        grams: 12000 // ← 12kg
    );

    expect($this->trip->fresh()->gramsReserved())->toBe(12000);
});

it('compte les bookings en paiement non expirés dans les kg réservés', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 8000,
        paymentExpiresAt: now()->addMinutes(10)
    );

    expect($this->trip->fresh()->gramsReserved())->toBe(8000);
});

it('ne compte pas les bookings en paiement expirés dans les kg réservés', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 9000,
        paymentExpiresAt: now()->subMinutes(10)
    );

    expect($this->trip->fresh()->gramsReserved())->toBe(0);
});

it('additionne les bookings confirmés et en paiement non expirés', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        grams: 10000
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 7000,
        paymentExpiresAt: now()->addMinutes(15)
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 5000,
        paymentExpiresAt: now()->subMinutes(15)
    );

    $trip = $this->trip->fresh();

    expect($trip->gramsReserved())->toBe(17000)
        ->and($trip->gramsDisponible())->toBe(33000);
});

it('canAcceptGrams tient compte des bookings confirmés et en paiement non expirés', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        grams: 30000
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 15000,
        paymentExpiresAt: now()->addMinutes(10)
    );

    $trip = $this->trip->fresh();

    expect($trip->canAcceptGrams(4000))->toBeTrue()
        ->and($trip->canAcceptGrams(6000))->toBeFalse();
});

it('ne considère pas un trajet terminé comme réservable', function (): void {
    expect(TripStatusEnum::COMPLETED->isReservable())->toBeFalse();
});

it('considère les trajets actifs et pending comme réservables', function (): void {
    expect(TripStatusEnum::ACTIVE->isReservable())->toBeTrue()
        ->and(TripStatusEnum::PENDING->isReservable())->toBeTrue();
});

it('scopeReservable inclut les trips ACTIVE', function (): void {
    expect(Trip::reservable()->where('id', $this->trip->id)->exists())->toBeTrue();
});

it('scopeReservable inclut les trips PENDING', function (): void {
    $trip = Trip::factory()->for($this->traveler)->create([
        'capacity' => 50000,
        'status'   => TripStatusEnum::PENDING,
    ]);

    expect(Trip::reservable()->where('id', $trip->id)->exists())->toBeTrue();
});

it('scopeReservable exclut les trips CANCELLED et COMPLETED', function (): void {
    $cancelled = Trip::factory()->for($this->traveler)->create([
        'capacity' => 50000,
        'status'   => TripStatusEnum::CANCELLED,
    ]);
    $completed = Trip::factory()->for($this->traveler)->create([
        'capacity' => 50000,
        'status'   => TripStatusEnum::COMPLETED,
    ]);

    $ids = Trip::reservable()->pluck('id');

    expect($ids)->not->toContain($cancelled->id)
        ->and($ids)->not->toContain($completed->id);
});

it('scopeReservable exclut les trips avec date passée', function (): void {
    $past = Trip::factory()->passé()->for($this->traveler)->create([
        'capacity' => 50000,
        'status'   => TripStatusEnum::ACTIVE,
    ]);

    expect(Trip::reservable()->where('id', $past->id)->exists())->toBeFalse();
});

it('scopeReservable exclut un trip saturé par des bookings CONFIRMEE', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        grams: 50000
    );

    expect(Trip::reservable()->where('id', $this->trip->id)->exists())->toBeFalse();
});

it('scopeReservable exclut un trip saturé par des EN_PAIEMENT non expirés', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::CONFIRMEE,
        grams: 40000
    );

    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 10000,
        paymentExpiresAt: now()->addMinutes(15)
    );

    expect(Trip::reservable()->where('id', $this->trip->id)->exists())->toBeFalse();
});

it('scopeReservable ne compte pas les EN_PAIEMENT expirés dans la capacité', function (): void {
    createCapacityBookingItemForTrip(
        trip: $this->trip,
        sender: $this->sender,
        status: BookingStatusEnum::EN_PAIEMENT,
        grams: 50000,
        paymentExpiresAt: now()->subMinutes(10)
    );

    expect(Trip::reservable()->where('id', $this->trip->id)->exists())->toBeTrue();
});

it('compte les bookings PENDING_APPROVAL dans les kg réservés', function (): void {
    $traveler = User::factory()->traveler()->create();
    $trip     = Trip::factory()->create([
        'user_id'  => $traveler->id,
        'capacity' => 20000,
    ]);
    $sender  = User::factory()->sender()->create();
    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::PENDING_APPROVAL,
    ]);
    BookingItem::factory()->create([
        'booking_id'  => $booking->id,
        'trip_id'     => $trip->id,
        'kg_reserved' => 5000,
    ]);

    expect($trip->gramsReserved())->toBe(5000);
});

it('scopeReservable exclut un trip saturé par des PENDING_APPROVAL', function (): void {
    $traveler = User::factory()->traveler()->create();
    $trip     = Trip::factory()->create([
        'user_id'  => $traveler->id,
        'capacity' => 5000,
        'status'   => TripStatusEnum::ACTIVE,
        'date'     => now()->addDays(5),
    ]);
    $sender  = User::factory()->sender()->create();
    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::PENDING_APPROVAL,
    ]);
    BookingItem::factory()->create([
        'booking_id'  => $booking->id,
        'trip_id'     => $trip->id,
        'kg_reserved' => 5000,
    ]);

    $result = Trip::reservable()->where('id', $trip->id)->exists();
    expect($result)->toBeFalse();
});
