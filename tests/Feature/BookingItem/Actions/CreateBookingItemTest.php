<?php

use App\Actions\BookingItem\CreateBookingItem;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('crée un booking item avec cohérence booking/trip/luggage', function () {
    $user = User::factory()->verified()->sender()->create();
    $trip = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
    ]);
    $luggage = Luggage::factory()->create([
        'user_id' => $user->id,
    ]);

    $data = [
        'kg_reserved' => 10.0,
        'price' => 75,
        'luggage_id' => $luggage->id,
    ];

    $item = CreateBookingItem::execute($booking, $data);

    expect($item)->toBeInstanceOf(BookingItem::class)
        ->and($item->booking_id)->toBe($booking->id)
        ->and($item->trip_id)->toBe($booking->trip_id)
        ->and($item->kg_reserved)->toEqual(10) // ✅ souple
        ->and($item->price)->toEqual(75);
});
