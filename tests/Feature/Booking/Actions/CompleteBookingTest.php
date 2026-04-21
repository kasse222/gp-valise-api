<?php

use App\Actions\Booking\CompleteBooking;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\BookingDelivered;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('livre une réservation avec succès', function () {
    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    actingAs($voyageur);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur);

    $result->load('statusHistories');

    expect($result->status)->toBe(BookingStatusEnum::LIVREE)
        ->and($result->statusHistories->last()->new_status)->toBe(BookingStatusEnum::LIVREE);
});

it('dispatch BookingDelivered lorsqu une réservation est livrée', function () {
    Event::fake();

    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    actingAs($voyageur);

    $result = app(CompleteBooking::class)->execute($booking, $voyageur);

    Event::assertDispatched(BookingDelivered::class, function (BookingDelivered $event) use ($result) {
        return $event->booking->id === $result->id
            && $event->booking->status === BookingStatusEnum::LIVREE;
    });
});

it('crée automatiquement un payout pending et une commission lorsqu une réservation est livrée', function () {
    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 120.50,
        'processed_at' => now(),
    ]);

    actingAs($voyageur);

    app(CompleteBooking::class)->execute($booking, $voyageur);

    $payout = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::PAYOUT)
        ->first();

    $fee = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::FEE)
        ->first();

    expect($payout)->not->toBeNull()
        ->and($payout->user_id)->toBe($voyageur->id)
        ->and($payout->status)->toBe(TransactionStatusEnum::PENDING)
        ->and((float) $payout->amount)->toBe(102.42);

    expect($fee)->not->toBeNull()
        ->and($fee->user_id)->toBe($voyageur->id)
        ->and($fee->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and((float) $fee->amount)->toBe(18.08);
});

it('ne crée pas de deuxième payout si un payout existe déjà', function () {
    $voyageur = User::factory()->traveler()->verified()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $expediteur = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $expediteur->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::LIVREE,
    ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 120.50,
        'processed_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id' => $voyageur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
        'status' => TransactionStatusEnum::PENDING,
        'amount' => 102.42,
        'processed_at' => null,
    ]);

    expect($booking->hasPayoutTransaction())->toBeTrue();
});
