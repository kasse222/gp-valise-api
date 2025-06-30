<?php

use App\Actions\Booking\ReserveBooking;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->expediteur = User::factory()->sender()->create();
    Auth::login($this->expediteur); // simulate logged-in user
});

it('crée une réservation avec une valise disponible', function () {
    $this->actingAs($this->expediteur);
    $trip = Trip::factory()->create(['capacity' => 30]); // Capacité 30 kg
    $luggage = Luggage::factory()->for($this->expediteur)->create([
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);
    $validated = [
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

    expect($booking)->toBeInstanceOf(Booking::class);
    expect($booking->trip_id)->toBe($trip->id);
    expect($booking->user_id)->toBe($this->expediteur->id);
    expect($booking->bookingItems)->toHaveCount(1);
    expect($booking->bookingItems->first()->luggage_id)->toBe($luggage->id);

    $luggage->refresh();
    expect($luggage->status)->toBe(LuggageStatusEnum::RESERVEE);
});

it('rejette une valise déjà réservée', function () {
    $trip = Trip::factory()->create(['capacity' => 30]);
    $luggage = Luggage::factory()->for($this->expediteur)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    $validated = [
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 10,
                'price' => 50,
            ],
        ],
    ];

    $this->expectException(ValidationException::class);
    app(ReserveBooking::class)->execute($validated);
});

it('rejette si la capacité est dépassée', function () {
    $trip = Trip::factory()->create(['capacity' => 5]); // ← Capacité trop faible
    $luggage = Luggage::factory()->for($this->expediteur)->create([
        'status' => LuggageStatusEnum::EN_ATTENTE,
    ]);

    $validated = [
        'trip_id' => $trip->id,
        'items' => [
            [
                'luggage_id' => $luggage->id,
                'kg_reserved' => 10,
                'price' => 50,
            ],
        ],
    ];

    $this->expectException(ValidationException::class);
    app(ReserveBooking::class)->execute($validated);
});
