<?php

use App\Actions\Booking\RunExpirePendingBookingsBatch;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('expire les bookings expirés par batch et libère les valises associées', function () {
    $sender = User::factory()->sender()->create();
    $trip = Trip::factory()->create();

    // Booking 1 expiré
    $booking1 = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinutes(10),
    ]);

    $luggage1 = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking1->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage1->id,
        'kg_reserved' => 10,
        'price' => 50,
    ]);

    // Booking 2 expiré
    $booking2 = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinutes(5),
    ]);

    $luggage2 = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking2->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage2->id,
        'kg_reserved' => 8,
        'price' => 40,
    ]);

    // Booking 3 encore valide
    $booking3 = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(10),
    ]);

    $luggage3 = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking3->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage3->id,
        'kg_reserved' => 6,
        'price' => 30,
    ]);

    $result = app(RunExpirePendingBookingsBatch::class)->execute(100);

    $booking1->refresh();
    $booking2->refresh();
    $booking3->refresh();

    $luggage1->refresh();
    $luggage2->refresh();
    $luggage3->refresh();

    expect($result['scanned'])->toBe(2)
        ->and($result['expired'])->toBe(2)
        ->and($result['skipped'])->toBe(0)
        ->and($result['failed'])->toBe(0);

    expect($booking1->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($booking2->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($booking3->status)->toBe(BookingStatusEnum::EN_PAIEMENT);

    expect($luggage1->status)->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->and($luggage2->status)->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->and($luggage3->status)->toBe(LuggageStatusEnum::RESERVEE);
});
