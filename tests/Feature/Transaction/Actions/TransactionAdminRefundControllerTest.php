<?php

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentResponseData;
use App\Enums\PaymentProviderEnum;
use App\Enums\CurrencyEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('gpvalise.fee_percentage', 10);

    $this->admin = User::factory()->admin()->create([
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);

    $this->sender = User::factory()->create([
        'role' => UserRoleEnum::SENDER,
        'verified_user' => true,
        'kyc_passed_at' => now(),
    ]);

    $this->traveler = User::factory()->create();

    $this->trip = Trip::factory()->create([
        'user_id' => $this->traveler->id,
    ]);

    $this->provider = mock(PaymentProvider::class);

    $this->provider->shouldReceive('refund')
        ->andReturn(new PaymentResponseData(
            provider: PaymentProviderEnum::FAKE,
            providerTransactionId: 'admin_refund_123',
            providerStatus: 'completed',
            amount: 10000,
            currency: CurrencyEnum::EUR,
            checkoutUrl: null,
            eventId: null,
            rawPayload: [],
        ));

    $resolver = mock(\App\Contracts\Payments\PaymentProviderResolverContract::class);
    $resolver->shouldReceive('resolveByKey')->andReturn($this->provider);
    app()->instance(\App\Contracts\Payments\PaymentProviderResolverContract::class, $resolver);
});

function createAdminRefundScenario(User $sender, Trip $trip)
{
    $booking = Booking::factory()->create([
        'user_id' => $sender->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $sender->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
        'currency' => CurrencyEnum::EUR,
        'method' => PaymentMethodEnum::CARD,
        'processed_at' => now(),
    ]);

    return [$booking, $charge];
}

it('permet à un admin de faire un refund via endpoint', function () {
    [$booking, $charge] = createAdminRefundScenario($this->sender, $this->trip);

    $this->actingAs($this->admin)
        ->postJson("/api/v1/transactions/{$charge->id}/admin-refund", [
            'reason' => 'Litige validé',
        ])
        ->assertOk()
        ->assertJsonPath('data.type.code', TransactionTypeEnum::REFUND->value)
        ->assertJsonPath('data.status.code', TransactionStatusEnum::COMPLETED->value);

    $this->assertDatabaseHas('transactions', [
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND->value,
        'provider_transaction_id' => 'admin_refund_123',
    ]);
});

it('refuse si utilisateur non admin', function () {
    [$booking, $charge] = createAdminRefundScenario($this->sender, $this->trip);

    expect($this->sender->isAdmin())->toBeFalse();
    expect($this->sender->can('adminRefund', $charge))->toBeFalse();

    $this->actingAs($this->sender)
        ->postJson("/api/v1/transactions/{$charge->id}/admin-refund", [
            'reason' => 'Tentative non autorisée',
        ])
        ->assertForbidden();
});

it('refuse sans reason', function () {
    [$booking, $charge] = createAdminRefundScenario($this->sender, $this->trip);

    $this->actingAs($this->admin)
        ->postJson("/api/v1/transactions/{$charge->id}/admin-refund", [
            'reason' => '',
        ])
        ->assertStatus(422);
});

it('refuse si booking pas en litige', function () {
    $booking = Booking::factory()->create([
        'user_id' => $this->sender->id,
        'trip_id' => $this->trip->id,
        'status' => BookingStatusEnum::LIVREE,
    ]);

    $charge = Transaction::factory()->create([
        'user_id' => $this->sender->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::CHARGE,
        'status' => TransactionStatusEnum::COMPLETED,
        'amount' => 100,
    ]);

    $this->actingAs($this->admin)
        ->postJson("/api/v1/transactions/{$charge->id}/admin-refund", [
            'reason' => 'Erreur',
        ])
        ->assertStatus(422);
});
