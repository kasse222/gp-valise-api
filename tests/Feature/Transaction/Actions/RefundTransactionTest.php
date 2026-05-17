<?php

use App\Actions\Transaction\CreateTransaction;
use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentResponseData;
use App\Enums\PaymentProviderEnum;
use App\Enums\CurrencyEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\TransactionCreated;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->trip = Trip::factory()->create();

    $this->provider = mock(PaymentProvider::class);

    $this->provider->shouldReceive('charge')
        ->andReturn(new PaymentResponseData(
            provider: PaymentProviderEnum::FAKE,
            providerTransactionId: 'txn_123',
            providerStatus: 'completed',
            amount: 10000,
            currency: CurrencyEnum::EUR,
            checkoutUrl: null,
            eventId: null,
            rawPayload: [],
        ));

    app()->instance(PaymentProvider::class, $this->provider);

    $this->action = app(CreateTransaction::class);
});

function validCreateTransactionData(Booking $booking): array
{
    return [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'method' => PaymentMethodEnum::CARD->value,
    ];
}

function createPayableBookingForCreateTransaction(User $user, Trip $trip): Booking
{
    return Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(10),
    ]);
}

it('crée une transaction CHARGE valide', function () {
    $booking = createPayableBookingForCreateTransaction($this->user, $this->trip);

    $transaction = $this->action->execute($this->user, validCreateTransactionData($booking));

    expect($transaction)
        ->toBeInstanceOf(Transaction::class)
        ->and($transaction->type)->toBe(TransactionTypeEnum::CHARGE)
        ->and($transaction->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($transaction->provider_transaction_id)->toBeString()->not->toBeEmpty();
});

it('dispatch TransactionCreated', function () {
    Event::fake();

    $booking = createPayableBookingForCreateTransaction($this->user, $this->trip);

    $transaction = $this->action->execute($this->user, validCreateTransactionData($booking));

    Event::assertDispatched(
        TransactionCreated::class,
        fn($event) => $event->transaction->id === $transaction->id
    );
});

it('refuse si booking appartient à un autre user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $booking = createPayableBookingForCreateTransaction($owner, $this->trip);

    $this->action->execute($other, validCreateTransactionData($booking));
})->throws(ValidationException::class);

it('refuse si booking pas EN_PAIEMENT', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::ANNULE,
    ]);

    $this->action->execute($this->user, validCreateTransactionData($booking));
})->throws(ValidationException::class);

it('refuse si paiement expiré', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinute(),
    ]);

    $this->action->execute($this->user, validCreateTransactionData($booking));
})->throws(ValidationException::class);

it('refuse double charge', function () {
    $booking = createPayableBookingForCreateTransaction($this->user, $this->trip);

    $this->action->execute($this->user, validCreateTransactionData($booking));

    expect(fn() => $this->action->execute($this->user, validCreateTransactionData($booking)))
        ->toThrow(ValidationException::class);

    expect(
        Transaction::query()
            ->where('booking_id', $booking->id)
            ->where('type', TransactionTypeEnum::CHARGE)
            ->count()
    )->toBe(1);
});
