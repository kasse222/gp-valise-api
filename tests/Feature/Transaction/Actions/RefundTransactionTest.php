<?php

use App\Actions\Transaction\RefundTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionRefunded;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('dispatch TransactionRefunded quand une transaction est remboursée', function () {
    Event::fake();

    $user = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 150,
        'processed_at' => now(),
    ]);

    $refund = app(RefundTransaction::class)->execute($charge, 'Test refund');

    Event::assertDispatched(TransactionRefunded::class, function (TransactionRefunded $event) use ($refund) {
        return $event->transaction->id === $refund->id;
    });
});

it('crée une transaction de refund pour une charge complétée', function () {
    $user = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 150,
        'processed_at' => now(),
    ]);

    $refund = app(RefundTransaction::class)->execute($charge);

    expect($refund->type)->toBe(TransactionTypeEnum::REFUND)
        ->and($refund->status)->toBeIn([
            TransactionStatusEnum::COMPLETED,
            TransactionStatusEnum::PENDING,
        ])
        ->and((float) $refund->amount)->toBe(150.0);
});

it('rejette le remboursement si le booking nest pas dans un statut autorisé', function () {
    $user = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatusEnum::ANNULE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 150,
        'processed_at' => now(),
    ]);

    expect(fn() => app(RefundTransaction::class)->execute($charge))
        ->toThrow(ValidationException::class, 'Ce booking ne peut pas déclencher de remboursement.');
});

it('rejette le remboursement si un payout existe déjà', function () {
    $user = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatusEnum::LIVREE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 150,
        'processed_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::PAYOUT,
        'status' => TransactionStatusEnum::PENDING,
        'amount' => 120,
    ]);

    expect(fn() => app(RefundTransaction::class)->execute($charge))
        ->toThrow(ValidationException::class, 'Ce booking ne peut pas déclencher de remboursement.');
});

it('rejette le remboursement si un refund existe déjà', function () {
    $user = User::factory()->sender()->verified()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 150,
        'processed_at' => now(),
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 150,
        'processed_at' => now(),
    ]);

    expect(fn() => app(RefundTransaction::class)->execute($charge))
        ->toThrow(ValidationException::class, 'Ce booking ne peut pas déclencher de remboursement.');
});
