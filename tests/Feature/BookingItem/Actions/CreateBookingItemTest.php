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

it('crée un booking item cohérent avec le booking, le trip et la valise', function () {
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
        'price' => 75.0,
        'luggage_id' => $luggage->id,
    ];

    $item = CreateBookingItem::execute($booking, $data);

    expect($item)
        ->toBeInstanceOf(BookingItem::class)
        ->and($item->booking_id)->toBe($booking->id)
        ->and($item->trip_id)->toBe($booking->trip_id)
        ->and($item->luggage_id)->toBe($luggage->id)
        ->and((float) $item->kg_reserved)->toBe(10.0)
        ->and((float) $item->price)->toBe(75.0);

    $this->assertDatabaseHas('booking_items', [
        'id' => $item->id,
        'booking_id' => $booking->id,
        'trip_id' => $booking->trip_id,
        'luggage_id' => $luggage->id,
    ]);
});
