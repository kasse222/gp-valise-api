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
    config()->set('gpvalise.fee_percentage', 10);
    config()->set('gpvalise.payment_fee_percentage', 2);

    $this->sender = User::factory()->create();
    $this->traveler = User::factory()->create();

    $this->trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
    ]);

    $this->action = app(CreatePayoutTransaction::class);
});

function createDeliveredBookingForPayoutAction(): Booking
{
    return Booking::factory()
        ->for(test()->sender)
        ->for(test()->trip)
        ->create([
            'status' => BookingStatusEnum::LIVREE,
        ]);
}

function createChargeForPayoutAction(Booking $booking, float $amount = 100): Transaction
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
    $booking = createDeliveredBookingForPayoutAction();

    createChargeForPayoutAction($booking, 100);

    $payout = $this->action->execute($booking);

    expect($payout)
        ->toBeInstanceOf(Transaction::class)
        ->and($payout->type)->toBe(TransactionTypeEnum::PAYOUT)
        ->and($payout->status)->toBe(TransactionStatusEnum::PENDING)
        ->and((float) $payout->amount)->toBe(90.0)
        ->and($payout->user_id)->toBe($this->traveler->id);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::FEE->value,
        'status' => TransactionStatusEnum::COMPLETED->value,
        'amount' => 10,
    ]);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT->value,
        'status' => TransactionStatusEnum::PENDING->value,
        'amount' => 90,
    ]);
});

it('refuse un payout si le booking nest pas livré', function () {
    $booking = Booking::factory()
        ->for($this->sender)
        ->for($this->trip)
        ->create([
            'status' => BookingStatusEnum::CONFIRMEE,
        ]);

    createChargeForPayoutAction($booking);

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un payout sans charge complétée', function () {
    $booking = createDeliveredBookingForPayoutAction();

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un double payout', function () {
    $booking = createDeliveredBookingForPayoutAction();

    createChargeForPayoutAction($booking);

    Transaction::factory()->create([
        'user_id' => $this->traveler->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
        'status' => TransactionStatusEnum::PENDING,
        'amount' => 90,
    ]);

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('refuse un payout si un refund existe déjà', function () {
    $booking = createDeliveredBookingForPayoutAction();

    createChargeForPayoutAction($booking);

    Transaction::factory()->create([
        'user_id' => $this->sender->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
    ]);

    $this->action->execute($booking);
})->throws(ValidationException::class);

it('crée une PAYMENT_FEE avec le bon montant et statut COMPLETED', function () {
    $booking = createDeliveredBookingForPayoutAction();
    $charge  = createChargeForPayoutAction($booking, 100);

    $this->action->execute($booking);

    $paymentFee = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::PAYMENT_FEE->value)
        ->firstOrFail();

    $expectedAmount = app(\App\Services\TransactionAmountCalculator::class)
        ->calculatePaymentFeeAmount($charge);

    expect((float) $paymentFee->amount)->toBe($expectedAmount)
        ->and($paymentFee->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($paymentFee->processed_at)->not->toBeNull();
});

it('profit_net calculable depuis la DB (FEE - PAYMENT_FEE)', function () {
    $booking = createDeliveredBookingForPayoutAction();
    createChargeForPayoutAction($booking, 100);

    $this->action->execute($booking);

    $fee = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::FEE->value)
        ->firstOrFail();

    $paymentFee = Transaction::query()
        ->where('booking_id', $booking->id)
        ->where('type', TransactionTypeEnum::PAYMENT_FEE->value)
        ->firstOrFail();

    $profitNet = (float) $fee->amount - (float) $paymentFee->amount;

    // fee_percentage=10%, payment_fee_percentage=2% on 100 → 10 - 2 = 8
    expect($profitNet)->toBe(8.0);
});

it('refuse un payout si une fee existe déjà', function () {
    $booking = createDeliveredBookingForPayoutAction();

    createChargeForPayoutAction($booking);

    Transaction::factory()->create([
        'user_id' => $this->traveler->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::FEE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 10,
    ]);

    $this->action->execute($booking);
})->throws(ValidationException::class);
