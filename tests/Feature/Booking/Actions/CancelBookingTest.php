<?php

use App\Actions\Booking\CancelBooking;
use App\Enums\BookingStatusEnum;
use App\Events\BookingCanceled;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('annule une réservation avec succès', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create([
            'status' => BookingStatusEnum::EN_ATTENTE,
        ]);

    $result = app(CancelBooking::class)->execute($booking, $user);

    expect($result)
        ->toBeInstanceOf(Booking::class)
        ->and($result->status)->toBe(BookingStatusEnum::ANNULE);
});

it('dispatch BookingCanceled lorsqu une réservation est annulée', function () {
    Event::fake();

    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create([
            'status' => BookingStatusEnum::EN_ATTENTE,
        ]);

    $result = app(CancelBooking::class)->execute($booking, $user);

    Event::assertDispatched(BookingCanceled::class, function (BookingCanceled $event) use ($result) {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::ANNULE;
    });
});
