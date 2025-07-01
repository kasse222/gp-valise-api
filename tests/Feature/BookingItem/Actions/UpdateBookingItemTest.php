<?php

use App\Actions\BookingItem\UpdateBookingItem;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('met à jour un booking item existant', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::ACCEPTE,
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'kg_reserved' => 5,
        'price' => 30,
    ]);

    $data = [
        'kg_reserved' => 20,
        'price' => 120,
    ];

    $updated = UpdateBookingItem::execute($item, $data);

    expect($updated->kg_reserved)->toEqual(20)
        ->and($updated->price)->toEqual(120);
});

it('refuse de mettre à jour un booking item si la réservation est finalisée', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::TERMINE, // ❌ final
    ]);

    $item = BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'kg_reserved' => 5,
        'price' => 30,
    ]);

    $data = [
        'kg_reserved' => 50,
        'price' => 150,
    ];

    expect(fn() => UpdateBookingItem::execute($item, $data))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
