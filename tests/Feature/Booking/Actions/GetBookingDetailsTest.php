<?php

use App\Actions\Booking\GetBookingDetails;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\BookingStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('récupère les détails d’une réservation avec ses relations', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create();

    $booking = Booking::factory()->for($user)->for($trip)->create();

    $luggage = Luggage::factory()->for($user)->create();
    $item = BookingItem::factory()->for($booking)->create([
        'luggage_id' => $luggage->id,
    ]);

    Transaction::factory()->for($booking)->create();
    BookingStatusHistory::factory()->for($booking)->create();

    $result = GetBookingDetails::execute($booking->id);

    expect($result)->toBeInstanceOf(Booking::class);
    expect($result->relationLoaded('bookingItems'))->toBeTrue();
    expect($result->bookingItems->first()->luggage->id)->toBe($luggage->id);
    expect($result->trip->user->id)->toBe($user->id);
    expect($result->transaction)->not()->toBeNull();
    expect($result->statusHistories)->not()->toBeEmpty();
});
