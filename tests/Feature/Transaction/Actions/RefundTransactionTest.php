<?php

use App\Actions\Transaction\RefundTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('dispatch TransactionRefunded quand une transaction est remboursée', function () {
    Event::fake();

    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_LITIGE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'amount'     => 150.00,
        'status'     => TransactionStatusEnum::COMPLETED,
    ]);

    $reason = 'Demande client';

    $refund = app(RefundTransaction::class)->execute($charge, $reason);

    Event::assertDispatched(TransactionRefunded::class, function (TransactionRefunded $event) use ($refund, $reason) {
        return $event->transaction->id === $refund->id
            && $event->transaction->type === TransactionTypeEnum::REFUND
            && $event->reason === $reason;
    });
});

it('crée une transaction de refund pour une charge complétée', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_LITIGE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 150.00,
    ]);

    $refund = app(RefundTransaction::class)->execute($charge);

    expect($refund->type)->toBe(TransactionTypeEnum::REFUND)
        ->and($refund->amount)->toBe(150.0)
        ->and($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($refund->booking_id)->toBe($booking->id)
        ->and($refund->user_id)->toBe($user->id);

    $charge->refresh();

    expect($charge->type)->toBe(TransactionTypeEnum::CHARGE)
        ->and($charge->status)->toBe(TransactionStatusEnum::COMPLETED);
});

it('rejette le remboursement si le booking nest pas dans un statut autorisé', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::CONFIRMEE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 150.00,
    ]);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    app(RefundTransaction::class)->execute($charge);
});

it('rejette le remboursement si un payout existe déjà', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_LITIGE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 150.00,
    ]);

    Transaction::factory()->create([
        'user_id'    => $trip->user_id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::PAYOUT,
        'status'     => TransactionStatusEnum::PENDING,
        'amount'     => 100.00,
    ]);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    app(RefundTransaction::class)->execute($charge);
});

it('rejette le remboursement si un refund existe déjà', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_LITIGE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::CHARGE,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 150.00,
    ]);

    Transaction::factory()->create([
        'user_id'    => $user->id,
        'booking_id' => $booking->id,
        'type'       => TransactionTypeEnum::REFUND,
        'status'     => TransactionStatusEnum::COMPLETED,
        'amount'     => 150.00,
    ]);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    app(RefundTransaction::class)->execute($charge);
});
