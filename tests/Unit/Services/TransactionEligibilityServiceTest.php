<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\TransactionEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(TransactionEligibilityService::class);
});

// Helper — booking LIVREE avec escrow libérable
function livreedBookingWithReleasableEscrow(): Booking
{
    return Booking::factory()->create([
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now()->subHours(49),
        'escrow_releasable_at' => now()->subHours(1), // ← délai écoulé
        'disputed_at'          => null,
    ]);
}

/*
|--------------------------------------------------------------------------
| Payout eligibility
|--------------------------------------------------------------------------
*/

it('autorise un payout si conditions OK', function (): void {
    $booking = livreedBookingWithReleasableEscrow();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeTrue();
});

it('refuse payout si escrow non libérable — délai non écoulé', function (): void {
    $booking = Booking::factory()->create([
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now(),
        'escrow_releasable_at' => now()->addHours(47), // ← pas encore libérable
        'disputed_at'          => null,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

it('refuse payout si dispute active', function (): void {
    $booking = Booking::factory()->create([
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now()->subHours(49),
        'escrow_releasable_at' => now()->subHours(1),
        'disputed_at'          => now()->subHours(2), // ← dispute active
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

it('refuse payout si refund existe', function (): void {
    $booking = livreedBookingWithReleasableEscrow();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::REFUND,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

it('refuse payout si déjà payout', function (): void {
    $booking = livreedBookingWithReleasableEscrow();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

it('refuse payout si une fee existe déjà', function (): void {
    $booking = livreedBookingWithReleasableEscrow();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::FEE,
    ]);

    expect($this->service->canCreatePayout($booking))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Refund eligibility — inchangé
|--------------------------------------------------------------------------
*/

it('autorise refund si booking confirmé avec charge complétée', function (): void {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::CONFIRMEE]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeTrue();
});

it('autorise refund si booking en litige avec charge complétée', function (): void {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::EN_LITIGE]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeTrue();
});

it('refuse refund si booking livré', function (): void {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::LIVREE]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeFalse();
});

it('refuse refund si refund existe déjà', function (): void {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::CONFIRMEE]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::REFUND,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeFalse();
});

it('refuse refund si payout existe', function (): void {
    $booking = Booking::factory()->create(['status' => BookingStatusEnum::CONFIRMEE]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
    ]);

    expect($this->service->canCreateRefund($booking))->toBeFalse();
});
