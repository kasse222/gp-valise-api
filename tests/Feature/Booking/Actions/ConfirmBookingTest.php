<?php

use App\Actions\Booking\ConfirmBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Luggage;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('confirme une réservation avec succès si la capacité le permet', function () {
    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
        'capacity' => 20,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(10),
    ]);

    $luggage = Luggage::factory()->for($expediteur)->create();

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 50,
    ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    actingAs($voyageur);

    $result = app(ConfirmBooking::class)->execute($booking, $voyageur);

    $result->load('statusHistories');

    expect($result->status)->toBe(BookingStatusEnum::CONFIRMEE)
        ->and($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::CONFIRMEE);
});

it('dispatch BookingConfirmed lorsqu une réservation est confirmée', function () {
    Event::fake();

    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
        'capacity' => 20,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(10),
    ]);

    $luggage = Luggage::factory()->for($expediteur)->create();

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 50,
    ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    actingAs($voyageur);

    $result = app(ConfirmBooking::class)->execute($booking, $voyageur);

    Event::assertDispatched(BookingConfirmed::class, function (BookingConfirmed $event) use ($result) {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::CONFIRMEE;
    });
});

it('rejette la confirmation si aucune transaction de charge complétée n’existe', function () {
    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
        'capacity' => 20,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(10),
    ]);

    $luggage = Luggage::factory()->for($expediteur)->create();

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'luggage_id' => $luggage->id,
        'kg_reserved' => 5,
        'price' => 50,
    ]);

    actingAs($voyageur);

    expect($booking->transaction()->exists())->toBeFalse();

    app(ConfirmBooking::class)->execute($booking, $voyageur);
})->throws(ValidationException::class, 'Le booking ne peut pas être confirmé sans paiement validé.');
