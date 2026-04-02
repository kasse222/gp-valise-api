<?php

use App\Actions\Transaction\RefundTransaction;
use App\Enums\TransactionStatusEnum;
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
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'status' => TransactionStatusEnum::COMPLETED, // adapte selon ton enum métier remboursable
    ]);

    $reason = 'Demande client';

    $refunded = app(RefundTransaction::class)->execute($transaction, $reason);

    Event::assertDispatched(TransactionRefunded::class, function (TransactionRefunded $event) use ($refunded, $reason) {
        return $event->transaction->id === $refunded->id
            && $event->reason === $reason;
    });
});
