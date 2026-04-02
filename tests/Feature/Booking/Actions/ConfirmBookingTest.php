<?php

use App\Actions\Booking\ConfirmBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\UserRoleEnum;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('confirme une réservation avec succès si la capacité le permet', function () {
    Event::fake();

    $voyageur = User::factory()->create([
        'role' => UserRoleEnum::TRAVELER,
    ]);

    $trip = Trip::factory()
        ->for($voyageur)
        ->create([
            'capacity' => 100,
        ]);

    $expediteur = User::factory()->create([
        'role' => UserRoleEnum::SENDER,
    ]);

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::EN_PAIEMENT,
            'payment_expires_at' => now()->addMinutes(15),
            'confirmed_at' => null,
            'cancelled_at' => null,
        ]);

    BookingItem::factory()->for($booking)->create([
        'kg_reserved' => 20,
        'trip_id' => $trip->id,
    ]);

    $result = app(ConfirmBooking::class)->execute($booking->id, $voyageur);

    expect($result->confirmed_at)->not->toBeNull()
        ->and($result->cancelled_at)->toBeNull()
        ->and($result->status)->toBe(BookingStatusEnum::CONFIRMEE);

    Event::assertDispatched(BookingConfirmed::class, function (BookingConfirmed $event) use ($booking) {
        return $event->booking->id === $booking->id
            && $event->booking->status === BookingStatusEnum::CONFIRMEE;
    });
});
