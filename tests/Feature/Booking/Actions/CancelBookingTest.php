<?php

use App\Actions\Booking\CancelBooking;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\User;

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
