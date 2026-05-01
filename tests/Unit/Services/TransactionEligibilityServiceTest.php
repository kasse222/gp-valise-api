<?php

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\TransactionEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TransactionEligibilityService::class);
});

/*
|--------------------------------------------------------------------------
| Payout eligibility
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

it('refuse payout si une fee existe déjà', function () {
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
        'type' => TransactionTypeEnum::FEE,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Refund eligibility
|--------------------------------------------------------------------------
*/

it('autorise refund si booking confirmé avec charge complétée', function () {
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

it('autorise refund si booking en litige avec charge complétée', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeTrue();
});

it('refuse refund si booking livré', function () {
    $booking = Booking::factory()->create([
        'status' => BookingStatusEnum::LIVREE,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeFalse();
});

it('refuse refund si refund existe déjà', function () {
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
        'type' => TransactionTypeEnum::REFUND,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeFalse();
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
