<?php

use App\Actions\Transaction\CreatePayoutTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->sender = User::factory()->create();
    $this->traveler = User::factory()->create();

    $this->trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
    ]);

    $this->action = app(CreatePayoutTransaction::class);
});

function createDeliveredBooking(): Booking
{
    return Booking::factory()
        ->for(test()->sender)
        ->for(test()->trip)
        ->create([
            'status' => BookingStatusEnum::LIVREE,
        ]);
}

function createChargeForBooking(Booking $booking, float $amount = 100): Transaction
{
    return Transaction::factory()->create([
        'user_id' => test()->sender->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => $amount,
        'processed_at' => now(),
    ]);
}

it('crée un payout et une fee pour un booking livré avec charge complétée', function () {
    $booking = createDeliveredBooking();

    createChargeForBooking($booking, 100);

    $payout = $this->action->execute($booking);

    expect($payout)
        ->toBeInstanceOf(Transaction::class)
        ->and($payout->type)->toBe(TransactionTypeEnum::PAYOUT)
        ->and($payout->status)->toBe(TransactionStatusEnum::PENDING)
        ->and((float) $payout->amount)->toBe(85.0)
        ->and($payout->user_id)->toBe($this->traveler->id);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::FEE->value,
        'status' => TransactionStatusEnum::COMPLETED->value,
        'amount' => 15,
    ]);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'amount' => 85,
    ]);
});

it('refuse un payout si le booking nest pas livré', function () {
    $booking = Booking::factory()
        ->for($this->sender)
        ->for($this->trip)
        ->create([
            'status' => BookingStatusEnum::CONFIRMEE,
        ]);

    createChargeForBooking($booking);

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un payout sans charge complétée', function () {
    $booking = createDeliveredBooking();

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un double payout', function () {
    $booking = createDeliveredBooking();

    createChargeForBooking($booking);

    Transaction::factory()->create([
        'user_id' => $this->traveler->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
        'status' => TransactionStatusEnum::PENDING,
        'amount' => 85,
    ]);

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un payout si un refund existe déjà', function () {
    $booking = createDeliveredBooking();

    createChargeForBooking($booking);

    Transaction::factory()->create([
        'user_id' => $this->sender->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
    ]);

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un payout si une fee existe déjà', function () {
    $booking = createDeliveredBooking();

    createChargeForBooking($booking);

    Transaction::factory()->create([
        'user_id' => $this->traveler->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::FEE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 15,
    ]);

    $this->action->execute($booking);
})->throws(ValidationException::class);
