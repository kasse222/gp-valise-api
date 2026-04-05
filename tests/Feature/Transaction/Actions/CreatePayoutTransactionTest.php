<?php

use App\Actions\Transaction\CreatePayoutTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('crée une transaction de payout et une commission si le booking est livré et qu une charge complétée existe', function () {
    $voyageur = User::factory()->create();
    $expediteur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::LIVREE,
        ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 120.50,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => now(),
    ]);

    $payout = app(CreatePayoutTransaction::class)->execute($booking);

    $fee = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::FEE)
        ->first();

    expect($fee)->not->toBeNull()
        ->and($fee->user_id)->toBe($voyageur->id)
        ->and($fee->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($fee->amount)->toBe(18.08)
        ->and($fee->currency)->toBe(CurrencyEnum::EUR)
        ->and($fee->method)->toBe(PaymentMethodEnum::CARTE_BANCAIRE)
        ->and($fee->processed_at)->not->toBeNull();

    expect($payout)
        ->toBeInstanceOf(Transaction::class)
        ->and($payout->user_id)->toBe($voyageur->id)
        ->and($payout->booking_id)->toBe($booking->id)
        ->and($payout->type)->toBe(TransactionTypeEnum::PAYOUT)
        ->and($payout->status)->toBe(TransactionStatusEnum::PENDING)
        ->and($payout->amount)->toBe(102.42)
        ->and($payout->currency)->toBe(CurrencyEnum::EUR)
        ->and($payout->method)->toBe(PaymentMethodEnum::CARTE_BANCAIRE)
        ->and($payout->processed_at)->toBeNull();
});

it('rejette le payout si le booking n est pas livré', function () {
    $voyageur = User::factory()->create();
    $expediteur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::CONFIRMEE,
        ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 120.50,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => now(),
    ]);

    app(CreatePayoutTransaction::class)->execute($booking);
})->throws(ValidationException::class, 'Ce booking ne peut pas déclencher de payout.');

it('rejette le payout si aucune charge complétée n existe', function () {
    $voyageur = User::factory()->create();
    $expediteur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::LIVREE,
        ]);

    app(CreatePayoutTransaction::class)->execute($booking);
})->throws(ValidationException::class, 'Ce booking ne peut pas déclencher de payout.');

it('rejette le payout si un payout existe déjà pour ce booking', function () {
    $voyageur = User::factory()->create();
    $expediteur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::LIVREE,
        ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 120.50,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id' => $voyageur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
        'status' => TransactionStatusEnum::PENDING,
        'amount' => 102.42,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => null,
    ]);

    app(CreatePayoutTransaction::class)->execute($booking);
})->throws(ValidationException::class, 'Ce booking ne peut pas déclencher de payout.');

it('rejette le payout si une commission existe déjà pour ce booking', function () {
    $voyageur = User::factory()->create();
    $expediteur = User::factory()->create();

    $trip = Trip::factory()->create([
        'user_id' => $voyageur->id,
    ]);

    $booking = Booking::factory()
        ->for($expediteur)
        ->for($trip)
        ->create([
            'status' => BookingStatusEnum::LIVREE,
        ]);

    Transaction::factory()->create([
        'user_id' => $expediteur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 120.50,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id' => $voyageur->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::FEE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 18.08,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => now(),
    ]);

    app(CreatePayoutTransaction::class)->execute($booking);
})->throws(ValidationException::class, 'Ce booking ne peut pas déclencher de payout.');
