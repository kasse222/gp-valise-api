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

    $resolver = mock(\App\Contracts\Payments\PaymentProviderResolverContract::class);
    $resolver->shouldReceive('resolve')->andReturn($this->provider);
    $resolver->shouldReceive('resolveByKey')->andReturn($this->provider);
    app()->instance(\App\Contracts\Payments\PaymentProviderResolverContract::class, $resolver);

    $this->action = app(CreateTransaction::class);
});

function validCreateTransactionDataForRefund(Booking $booking): array
{
    return [
        'booking_id' => $booking->id,
        'amount' => 150.00,
        'currency' => CurrencyEnum::EUR->value,
        'method' => PaymentMethodEnum::CARD->value,
    ];
}

function createPayableBookingForRefund(User $user, Trip $trip): Booking
{
    return Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);
}

it('crée une charge si le booking est en paiement et non expiré', function () {
    $booking = createPayableBookingForRefund($this->user, $this->trip);

    $transaction = $this->action->execute($this->user, validCreateTransactionDataForRefund($booking));

    expect($transaction)
        ->toBeInstanceOf(Transaction::class)
        ->and($transaction->booking_id)->toBe($booking->id)
        ->and($transaction->user_id)->toBe($this->user->id)
        ->and($transaction->type)->toBe(TransactionTypeEnum::CHARGE)
        ->and($transaction->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($transaction->provider_transaction_id)->toBeString()->not->toBeEmpty();
});

it('dispatch TransactionCreated quand une charge est créée', function () {
    Event::fake();

    $booking = createPayableBookingForRefund($this->user, $this->trip);

    $transaction = $this->action->execute($this->user, validCreateTransactionDataForRefund($booking));

    Event::assertDispatched(TransactionCreated::class, function (TransactionCreated $event) use ($transaction) {
        return $event->transaction->id === $transaction->id;
    });
});

it('rejette la création si le booking ne lui appartient pas', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $booking = createPayableBookingForRefund($owner, $this->trip);

    $this->action->execute($otherUser, validCreateTransactionDataForRefund($booking));
})->throws(ValidationException::class);

it('rejette la création si le booking n’est pas en paiement', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::ANNULE,
        'payment_expires_at' => now()->addMinutes(15),
    ]);

    $this->action->execute($this->user, validCreateTransactionDataForRefund($booking));
})->throws(ValidationException::class);

it('rejette la création si le délai de paiement a expiré', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->subMinute(),
    ]);

    $this->action->execute($this->user, validCreateTransactionDataForRefund($booking));
})->throws(ValidationException::class);

it('refuse une deuxième charge pour le même booking', function () {
    $booking = createPayableBookingForRefund($this->user, $this->trip);

    $this->action->execute($this->user, validCreateTransactionDataForRefund($booking));

    expect(fn() => $this->action->execute($this->user, validCreateTransactionDataForRefund($booking)))
        ->toThrow(ValidationException::class);

    expect(
        Transaction::query()
            ->where('booking_id', $booking->id)
            ->where('type', TransactionTypeEnum::CHARGE)
            ->count()
    )->toBe(1);
});

it('stocke le provider_transaction_id lors de la création', function () {
    $booking = createPayableBookingForRefund($this->user, $this->trip);

    $transaction = $this->action->execute($this->user, validCreateTransactionDataForRefund($booking));

    expect($transaction->provider_transaction_id)->toBeString()->not->toBeEmpty();
});
