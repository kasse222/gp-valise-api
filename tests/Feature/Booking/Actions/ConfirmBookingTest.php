<?php

use App\Actions\Booking\ConfirmBooking;
use App\Enums\BookingStatusEnum;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('confirme une réservation avec succès si la capacité le permet', function () {
    $voyageur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
        'capacity' => 20,
    ]);

    $expediteur = User::factory()->create();

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::EN_PAIEMENT,
            'payment_expires_at' => now()->addMinutes(10),
        ]);

    $luggage = Luggage::factory()->for($expediteur)->create();

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 50,
    ]);

    $result = app(ConfirmBooking::class)->execute($booking, $voyageur);

    $result->load('statusHistories');

    expect($result->status)->toBe(BookingStatusEnum::CONFIRMEE)
        ->and($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::CONFIRMEE);
});

it('dispatch BookingConfirmed lorsqu une réservation est confirmée', function () {
    Event::fake();

    $voyageur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
        'capacity' => 20,
    ]);

    $expediteur = User::factory()->create();

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::EN_PAIEMENT,
            'payment_expires_at' => now()->addMinutes(10),
        ]);

    $luggage = Luggage::factory()->for($expediteur)->create();

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 50,
    ]);

    $result = app(ConfirmBooking::class)->execute($booking, $voyageur);

    Event::assertDispatched(BookingConfirmed::class, function (BookingConfirmed $event) use ($result) {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::CONFIRMEE;
    });
});
