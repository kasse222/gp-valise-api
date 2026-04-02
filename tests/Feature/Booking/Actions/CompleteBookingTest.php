<?php

use App\Actions\Booking\CompleteBooking;
use App\Enums\BookingStatusEnum;
use App\Events\BookingDelivered;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('livre une réservation avec succès', function () {
    $voyageur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $expediteur = User::factory()->create();

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::CONFIRMEE,
        ]);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur);

    $result->load('statusHistories');

    expect($result->status)->toBe(BookingStatusEnum::LIVREE)
        ->and($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::LIVREE);
});

it('dispatch BookingDelivered lorsqu une réservation est livrée', function () {
    Event::fake();

    $voyageur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $expediteur = User::factory()->create();

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::CONFIRMEE,
        ]);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur);

    Event::assertDispatched(BookingDelivered::class, function (BookingDelivered $event) use ($result) {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::LIVREE;
    });
});
