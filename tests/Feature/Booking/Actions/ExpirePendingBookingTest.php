<?php

use App\Actions\Booking\ExpirePendingBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('expire une réservation en attente de paiement et libère les valises', function () {
    $sender = User::factory()->sender()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinutes(5),
    ]);

    $luggage = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 10,
        'price' => 50,
    ]);

    $expiredBooking = app(ExpirePendingBooking::class)->execute($booking);

    $luggage->refresh();

    expect($expiredBooking->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($expiredBooking->payment_expires_at)->toBeNull()
        ->and($luggage->status)->toBe(LuggageStatusEnum::EN_ATTENTE);
});
