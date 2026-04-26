<?php

use App\Actions\Booking\DeleteBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\User;
use App\Models\Trip;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('supprime une réservation et remet les bagages en attente', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $luggage = Luggage::factory()->create([
        'user_id' => $user->id,
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    $booking = Booking::factory()->for($user)->for($trip)->create([
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    BookingItem::factory()->for($booking)->create([
        'luggage_id' => $luggage->id,
        'kg_reserved' => 10,
    ]);

    app(DeleteBooking::class)->execute($booking);

    expect(Booking::find($booking->id))->toBeNull();
    expect(BookingItem::where('booking_id', $booking->id)->count())->toBe(0);
    expect($luggage->fresh()->status)->toBe(LuggageStatusEnum::EN_ATTENTE);
});
