<?php

use App\Actions\Booking\ExpirePendingBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Events\BookingExpired;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('expire un booking de manière idempotente', function () {
    $sender = User::factory()->sender()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinutes(10),
        'expired_at' => null,
    ]);

    $luggage = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 25,
    ]);

    $action = app(ExpirePendingBooking::class);

    $firstResult = $action->execute($booking);

    $booking->refresh();
    $luggage->refresh();

    expect($firstResult->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($booking->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($booking->expired_at)->not->toBeNull()
        ->and($booking->payment_expires_at)->toBeNull()
        ->and($luggage->status)->toBe(LuggageStatusEnum::EN_ATTENTE);

    $firstExpiredAt = $booking->expired_at;
    $historyCountAfterFirstCall = $booking->statusHistories()->count();

    $secondResult = $action->execute($booking);

    $booking->refresh();
    $luggage->refresh();

    expect($secondResult->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($booking->status)->toBe(BookingStatusEnum::EXPIREE)
        ->and($booking->expired_at?->toDateTimeString())->toBe($firstExpiredAt?->toDateTimeString())
        ->and($booking->payment_expires_at)->toBeNull()
        ->and($luggage->status)->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->and($booking->statusHistories()->count())->toBe($historyCountAfterFirstCall);
});

it('dispatch l event BookingExpired lorsqu une réservation expire', function () {
    Event::fake();

    $sender = User::factory()->sender()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinutes(10),
        'expired_at' => null,
    ]);

    $luggage = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 25,
    ]);

    app(ExpirePendingBooking::class)->execute($booking);

    Event::assertDispatched(BookingExpired::class, function (BookingExpired $event) use ($booking) {
        return $event->booking->id === $booking->id
            && $event->booking->status === BookingStatusEnum::EXPIREE;
    });
});
