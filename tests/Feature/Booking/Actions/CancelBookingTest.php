<?php

use App\Actions\Booking\CancelBooking;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

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

    Sanctum::actingAs($user);

    $result = (new CancelBooking())->execute($booking->id);

    expect($result)
        ->toBeInstanceOf(Booking::class)
        ->status->toBe(BookingStatusEnum::ANNULE);
});
