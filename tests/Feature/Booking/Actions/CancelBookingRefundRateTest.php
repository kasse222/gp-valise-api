<?php

declare(strict_types=1);

use App\Actions\Booking\CancelBooking;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->traveler = User::factory()->traveler()->verified()->create();
    $this->sender   = User::factory()->sender()->verified()->create();
});

it('remboursement 100% si sender annule depuis EN_PAIEMENT', function (): void {
    $trip    = Trip::factory()->create(['user_id' => $this->traveler->id]);
    $booking = Booking::factory()->pendingPayment()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $trip->id,
    ]);

    $result = app(CancelBooking::class)->execute($booking, $this->sender, 'sender');

    expect($result->refund_rate)->toBe(100)
        ->and($result->status)->toBe(BookingStatusEnum::ANNULE);
});

it('remboursement 100% si annulation > 48h avant départ', function (): void {
    $trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
        'date'    => now()->addDays(5),
    ]);
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $trip->id,
    ]);

    $result = app(CancelBooking::class)->execute($booking, $this->sender, 'sender');

    expect($result->refund_rate)->toBe(100);
});

it('remboursement 70% si annulation < 48h avant départ', function (): void {
    $trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
        'date'    => now()->addHours(24),
    ]);
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $trip->id,
    ]);

    $result = app(CancelBooking::class)->execute($booking, $this->sender, 'sender');

    expect($result->refund_rate)->toBe(70);
});

it('remboursement 0% si no-show (date déjà passée)', function (): void {
    $trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
        'date'    => now()->subDay(),
    ]);
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $trip->id,
    ]);

    $result = app(CancelBooking::class)->execute($booking, $this->sender, 'sender');

    expect($result->refund_rate)->toBe(0);
});

it('remboursement 100% si voyageur annule', function (): void {
    $trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
        'date'    => now()->addHours(12), // < 48h mais c'est le traveler
    ]);
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $trip->id,
    ]);

    $result = app(CancelBooking::class)->execute($booking, $this->traveler, 'traveler');

    expect($result->refund_rate)->toBe(100)
        ->and($result->cancel_reason)->toBe('Annulation par le voyageur');
});

it('cancel_reason enregistré selon l\'acteur', function (): void {
    $trip    = Trip::factory()->create(['user_id' => $this->traveler->id, 'date' => now()->addDays(5)]);
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $trip->id,
    ]);

    $result = app(CancelBooking::class)->execute($booking, $this->sender, 'sender');

    expect($result->cancel_reason)->toBe('Annulation par l\'expéditeur');
});
