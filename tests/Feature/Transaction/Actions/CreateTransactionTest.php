<?php

use App\Actions\Transaction\CreateTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Events\TransactionCreated;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('crée une transaction si le booking est en paiement, non expiré et sans transaction existante', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    $transaction = app(CreateTransaction::class)->execute($user, $data);

    expect($transaction)
        ->toBeInstanceOf(Transaction::class)
        ->and($transaction->booking_id)->toBe($booking->id)
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->status)->toBe(TransactionStatusEnum::COMPLETED);
});

it('dispatch TransactionCreated quand une transaction est créée', function () {
    Event::fake();

    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    $transaction = app(CreateTransaction::class)->execute($user, $data);

    Event::assertDispatched(TransactionCreated::class, function (TransactionCreated $event) use ($transaction, $booking, $user) {
        return $event->transaction->id === $transaction->id
            && $event->transaction->booking_id === $booking->id
            && $event->transaction->user_id === $user->id;
    });
});

it('rejette la création si le booking ne lui appartient pas', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $owner->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    app(CreateTransaction::class)->execute($otherUser, $data);
})->throws(ValidationException::class, 'Ce booking ne vous appartient pas.');

it('rejette la création si le booking n’est pas en paiement', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::ANNULE,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    app(CreateTransaction::class)->execute($user, $data);
})->throws(ValidationException::class, 'Ce booking n’est pas dans un état permettant un paiement.');

it('rejette la création si le délai de paiement du booking a expiré', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinute(),
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    app(CreateTransaction::class)->execute($user, $data);
})->throws(ValidationException::class, 'Le délai de paiement de ce booking a expiré.');

it('rejette la création si une transaction existe déjà pour ce booking', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'status' => TransactionStatusEnum::PENDING,
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    app(CreateTransaction::class)->execute($user, $data);
})->throws(ValidationException::class, 'Une transaction existe déjà pour ce booking.');
