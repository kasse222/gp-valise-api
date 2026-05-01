<?php

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TransactionEligibilityService();
});

/*
|--------------------------------------------------------------------------
| 🔹 PAYOUT
|--------------------------------------------------------------------------
*/

it('autorise un payout si conditions OK', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::LIVREE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeTrue();
});

it('refuse payout si refund existe', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::LIVREE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

it('refuse payout si déjà payout', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::LIVREE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| 🔹 REFUND
|--------------------------------------------------------------------------
*/

it('autorise refund si conditions OK', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeTrue();
});

it('refuse refund si payout existe', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| 🔹 REFUNDABLE AMOUNT
|--------------------------------------------------------------------------
*/

it('calcule correctement le montant remboursable (charge - fee)', function () {
    $booking = Booking::factory()->create();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::FEE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 10,
    ]);

    $amount = $this->service->refundableAmount($booking);

    expect($amount)->toBe(90.0);
});
