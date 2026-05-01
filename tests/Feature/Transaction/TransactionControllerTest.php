<?php

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentResult;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->sender = User::factory()->create();

    $this->sender->forceFill([
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ])->save();

    $this->traveler = User::factory()->create();

    $this->trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
    ]);

    $this->provider = mock(PaymentProvider::class);

    $this->provider->shouldReceive('charge')
        ->andReturn(new PaymentResult(
            success: true,
            providerTransactionId: 'txn_123',
            status: 'completed',
            message: null,
        ));

    $this->provider->shouldReceive('refund')
        ->andReturn(new PaymentResult(
            success: true,
            providerTransactionId: 'refund_test_123',
            status: 'completed',
            message: null,
        ));

    app()->forgetInstance(PaymentProvider::class);
    app()->instance(PaymentProvider::class, $this->provider);
});

function createPayableBookingForTransactionController(User $sender, Trip $trip): Booking
{
    return Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addMinutes(15),
    ]);
}

it('liste les transactions de l’utilisateur connecté', function () {
    Transaction::factory()->count(2)->create([
        'user_id' => $this->sender->id,
    ]);

    Transaction::factory()->create();

    $this->actingAs($this->sender)
        ->getJson('/api/v1/transactions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('affiche une transaction appartenant au booking de l’utilisateur', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $this->sender->id,
        'booking_id' => $booking->id,
    ]);

    $this->actingAs($this->sender)
        ->getJson("/api/v1/transactions/{$transaction->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $transaction->id);
});

it('crée une transaction charge via endpoint store', function () {
    $booking = createPayableBookingForTransactionController($this->sender, $this->trip);

    $payload = [
        'booking_id' => $booking->id,
        'amount' => 100,
        'currency' => CurrencyEnum::EUR->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    $this->actingAs($this->sender)
        ->postJson('/api/v1/transactions', $payload)
        ->assertCreated()
        ->assertJsonPath('data.type.code', TransactionTypeEnum::CHARGE->value)
        ->assertJsonPath('data.status.code', TransactionStatusEnum::COMPLETED->value);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'user_id' => $this->sender->id,
        'type' => TransactionTypeEnum::CHARGE->value,
        'status' => TransactionStatusEnum::COMPLETED->value,
        'provider_transaction_id' => 'txn_123',
    ]);
});

it('refuse la création de charge si le booking appartient à un autre utilisateur', function () {
    $otherUser = User::factory()->create([
        'verified_user' => true,
    ]);

    $booking = createPayableBookingForTransactionController($otherUser, $this->trip);

    $payload = [
        'booking_id' => $booking->id,
        'amount' => 100,
        'currency' => CurrencyEnum::EUR->value,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE->value,
    ];

    $this->actingAs($this->sender)
        ->postJson('/api/v1/transactions', $payload)
        ->assertStatus(422);
});

it('rembourse une charge via endpoint refund', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $this->sender->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARTE_BANCAIRE,
        'processed_at' => now(),
    ]);

    $this->actingAs($this->sender)
        ->postJson("/api/v1/transactions/{$charge->id}/refund", [
            'reason' => 'Demande de remboursement',
        ])
        ->assertOk()
        ->assertJsonPath('data.type.code', TransactionTypeEnum::REFUND->value)
        ->assertJsonPath('data.status.code', TransactionStatusEnum::COMPLETED->value);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'user_id' => $this->sender->id,
        'type' => TransactionTypeEnum::REFUND->value,
        'status' => TransactionStatusEnum::COMPLETED->value,
        'amount' => 90,
        'provider_transaction_id' => 'refund_test_123',
    ]);
});

it('refuse un refund si la transaction ne correspond pas au booking de l’utilisateur', function () {
    $otherUser = User::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $otherUser->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::CONFIRMEE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
    ]);

    $this->actingAs($this->sender)
        ->postJson("/api/v1/transactions/{$charge->id}/refund", [
            'reason' => 'Tentative non autorisée',
        ])
        ->assertForbidden();
});
