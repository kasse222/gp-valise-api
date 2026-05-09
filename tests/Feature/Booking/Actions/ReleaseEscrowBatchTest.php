<?php

declare(strict_types=1);

use App\Actions\Booking\ReleaseEscrowBatch;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Jobs\ReleaseEscrowPayoutJob;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->batch = app(ReleaseEscrowBatch::class);
});

function escrowReleasableBooking(): Booking
{
    $sender   = User::factory()->create();
    $traveler = User::factory()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);

    $booking = Booking::factory()
        ->for($sender)
        ->for($trip)
        ->create([
            'status'               => BookingStatusEnum::LIVREE,
            'delivered_at'         => now()->subHours(49),
            'escrow_releasable_at' => now()->subHours(1),
            'disputed_at'          => null,
        ]);

    Transaction::factory()->create([
        'user_id'      => $sender->id,
        'booking_id'   => $booking->id,
        'type'         => TransactionTypeEnum::CHARGE,
        'status'       => TransactionStatusEnum::COMPLETED,
        'amount'       => 10000,
        'processed_at' => now(),
    ]);

    return $booking;
}

it('dispatche un job par booking escrow libérable', function (): void {
    Queue::fake();

    $booking1 = escrowReleasableBooking();
    $booking2 = escrowReleasableBooking();

    $count = $this->batch->execute();

    expect($count)->toBe(2);

    Queue::assertPushed(ReleaseEscrowPayoutJob::class, 2);
});

it('ignore les bookings avec escrow non encore libérable', function (): void {
    Queue::fake();

    $sender   = User::factory()->create();
    $traveler = User::factory()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);

    Booking::factory()->for($sender)->for($trip)->create([
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now(),
        'escrow_releasable_at' => now()->addHours(47), // ← pas encore libérable
        'disputed_at'          => null,
    ]);

    $count = $this->batch->execute();

    expect($count)->toBe(0);
    Queue::assertNothingPushed();
});

it('ignore les bookings avec dispute active', function (): void {
    Queue::fake();

    $sender   = User::factory()->create();
    $traveler = User::factory()->create();
    $trip     = Trip::factory()->create(['user_id' => $traveler->id]);

    Booking::factory()->for($sender)->for($trip)->create([
        'status'               => BookingStatusEnum::LIVREE,
        'delivered_at'         => now()->subHours(49),
        'escrow_releasable_at' => now()->subHours(1),
        'disputed_at'          => now()->subHours(2), // ← dispute active
    ]);

    $count = $this->batch->execute();

    expect($count)->toBe(0);
    Queue::assertNothingPushed();
});

it('ignore les bookings avec payout déjà existant', function (): void {
    Queue::fake();

    $booking = escrowReleasableBooking();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
        'status'     => TransactionStatusEnum::PENDING,
        'amount'     => 9000,
    ]);

    $count = $this->batch->execute();

    expect($count)->toBe(0);
    Queue::assertNothingPushed();
});

it('ignore les bookings avec refund existant', function (): void {
    Queue::fake();

    $booking = escrowReleasableBooking();

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::REFUND,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 10000,
    ]);

    $count = $this->batch->execute();

    expect($count)->toBe(0);
    Queue::assertNothingPushed();
});

it('retourne 0 si aucun booking éligible', function (): void {
    Queue::fake();

    $count = $this->batch->execute();

    expect($count)->toBe(0);
    Queue::assertNothingPushed();
});
