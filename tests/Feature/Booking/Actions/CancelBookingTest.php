<?php

use App\Actions\Booking\CancelBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Events\BookingCanceled;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('annule une réservation en paiement avec succès', function () {
    $sender = User::factory()->sender()->verified()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $luggage = Luggage::factory()->for($sender)->create([
        'status' => LuggageStatusEnum::RESERVEE,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 30,
    ]);

    actingAs($sender);

    $result = app(CancelBooking::class)->execute($booking, $sender);

    $luggage->refresh();
    $result->load('statusHistories');

    expect($result)
        ->toBeInstanceOf(Booking::class)
        ->and($result->status)->toBe(BookingStatusEnum::ANNULE)
        ->and($result->cancelled_at)->not->toBeNull()
        ->and($luggage->status)->toBe(LuggageStatusEnum::EN_ATTENTE)
        ->and($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::ANNULE);
});
it('peut annuler une réservation sans erreur de type', function () {
    $user = User::factory()->sender()->verified()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatusEnum::ANNULE);
});
it('dispatch BookingCanceled lorsqu une réservation est annulée', function () {
    Event::fake();

    $sender = User::factory()->sender()->verified()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    actingAs($sender);

    $result = app(CancelBooking::class)->execute($booking, $sender);

    Event::assertDispatched(BookingCanceled::class, function (BookingCanceled $event) use ($result) {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::ANNULE;
    });
});
