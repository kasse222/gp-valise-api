<?php

use App\Actions\Transaction\CreateTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Events\TransactionCreated;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('dispatch TransactionCreated quand une transaction est créée', function () {
    Event::fake();

    $user = User::factory()->create();
    $trip = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
    ]);

    $data = [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => 'EUR', // adapte si tu utilises un enum/cast spécifique
        'status' => TransactionStatusEnum::PENDING->value,
        'method' => PaymentMethodEnum::cases()[0]->value,
    ];

    $transaction = app(CreateTransaction::class)->execute($user, $data);

    Event::assertDispatched(TransactionCreated::class, function (TransactionCreated $event) use ($transaction, $booking, $user) {
        return $event->transaction->id === $transaction->id
            && $event->transaction->booking_id === $booking->id
            && $event->transaction->user_id === $user->id;
    });
});
