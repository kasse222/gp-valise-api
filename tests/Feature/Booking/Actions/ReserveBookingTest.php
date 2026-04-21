<?php

use App\Actions\Booking\ReserveBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->expediteur = User::factory()->sender()->verified()->create();
    $this->actingAs($this->expediteur);
});

it('crée une réservation avec une valise disponible', function () {
    $trip = Trip::factory()->create([
        'capacity' => 30,
        'user_id' => User::factory()->traveler()->create()->id,
    ]);

    $luggage = Luggage::factory()->for($this->expediteur)->create([
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $validated = [
        'user_id' => $this->expediteur->id,
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 10,
                'price' => 50,
            ],
        ],
    ];

    $booking = app(ReserveBooking::class)->execute($validated);

    expect($booking)->toBeInstanceOf(Booking::class)
        ->and($booking->trip_id)->toBe($trip->id)
        ->and($booking->user_id)->toBe($this->expediteur->id)
        ->and($booking->status)->toBe(BookingStatusEnum::EN_PAIEMENT)
        ->and($booking->payment_expires_at)->not->toBeNull()
        ->and($booking->bookingItems)->toHaveCount(1)
        ->and($booking->bookingItems->first()->luggage_id)->toBe($luggage->id);

    $luggage->refresh();

    expect($luggage->status)->toBe(LuggageStatusEnum::RESERVEE);
});

it('rejette une valise déjà réservée', function () {
    $trip = Trip::factory()->create([
        'capacity' => 30,
        'user_id' => User::factory()->traveler()->create()->id,
    ]);

    $luggage = Luggage::factory()->for($this->expediteur)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    $validated = [
        'user_id' => $this->expediteur->id,
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 10,
                'price' => 50,
            ],
        ],
    ];

    expect(fn() => app(ReserveBooking::class)->execute($validated))
        ->toThrow(ValidationException::class);
});

it('rejette si la capacité est dépassée', function () {
    $trip = Trip::factory()->create([
        'capacity' => 5,
        'user_id' => User::factory()->traveler()->create()->id,
    ]);

    $luggage = Luggage::factory()->for($this->expediteur)->create([
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $validated = [
        'user_id' => $this->expediteur->id,
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 10,
                'price' => 50,
            ],
        ],
    ];

    expect(fn() => app(ReserveBooking::class)->execute($validated))
        ->toThrow(ValidationException::class);
});
