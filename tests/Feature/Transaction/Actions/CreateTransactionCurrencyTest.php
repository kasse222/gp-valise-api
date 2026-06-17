<?php

declare(strict_types=1);

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Actions\Transaction\CreateTransaction;
use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;

// ─── F-007 — résolution devise depuis pays ─────────────────────────────────

it('CreateTransaction résout XOF depuis country SN si currency absent (F-007)', function () {
    $sender  = User::factory()->create(['role' => UserRoleEnum::SENDER]);
    $traveler = User::factory()->create(['role' => UserRoleEnum::TRAVELER]);
    $trip = Trip::factory()->create(['user_id' => $traveler->id]);
    $booking = Booking::factory()->create([
        'user_id'          => $sender->id,
        'trip_id'          => $trip->id,
        'status'           => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addHour(),
    ]);

    \Illuminate\Support\Facades\Http::fake([
        '*/checkout-invoice/create' => \Illuminate\Support\Facades\Http::response([
            'token' => 'pd_tok_sn_001',
        ], 200),
    ]);

    config([
        'payment_providers.paydunya.enabled'      => true,
        'payment_providers.paydunya.master_key'   => 'mk',
        'payment_providers.paydunya.private_key'  => 'pk',
        'payment_providers.paydunya.token'        => 'tk',
        'payment_providers.paydunya.sandbox'      => true,
        'payment_providers.paydunya.callback_url' => 'https://example.com/wh',
        'payment_providers.paydunya.success_url'  => 'https://example.com/ok',
        'payment_providers.paydunya.cancel_url'   => 'https://example.com/ko',
    ]);

    $transaction = app(CreateTransaction::class)->execute($sender, [
        'booking_id' => $booking->id,
        'amount'     => 15000,
        'country'    => 'SN',
        'method'     => PaymentMethodEnum::MOBILE_MONEY->value,
        // currency absent → doit être résolu via forCountry('SN') = XOF
    ]);

    expect($transaction->currency)->toBe(CurrencyEnum::XOF);
});

it('CreateTransaction résout MAD depuis country MA si currency absent (F-007)', function () {
    $sender  = User::factory()->create(['role' => UserRoleEnum::SENDER]);
    $traveler = User::factory()->create(['role' => UserRoleEnum::TRAVELER]);
    $trip = Trip::factory()->create(['user_id' => $traveler->id]);
    $booking = Booking::factory()->create([
        'user_id'          => $sender->id,
        'trip_id'          => $trip->id,
        'status'           => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addHour(),
    ]);

    \Stripe\StripeClient::class; // juste pour l'autoload

    config([
        'payment_providers.stripe.api_key'       => 'sk_test_fake',
        'payment_providers.stripe.webhook_secret' => 'whsec_fake',
        'payment_providers.stripe.success_url'    => 'https://example.com/success',
        'payment_providers.stripe.cancel_url'     => 'https://example.com/cancel',
    ]);

    // Mock Stripe — on vérifie juste la résolution de devise, pas l'appel Stripe
    $mockProvider = \Mockery::mock(\App\Contracts\Payments\PaymentProvider::class);
    $mockProvider->shouldReceive('charge')->andReturn(new \App\Data\Payments\PaymentResponseData(
        provider: \App\Enums\PaymentProviderEnum::STRIPE,
        providerTransactionId: 'pi_ma_001',
        providerStatus: 'pending',
        amount: 15000,
        currency: CurrencyEnum::MAD,
        checkoutUrl: 'https://checkout.stripe.com/pay/pi_ma_001',
        eventId: 'cs_001',
        rawPayload: [],
    ));

    $mockResolver = \Mockery::mock(\App\Contracts\Payments\PaymentProviderResolverContract::class);
    $mockResolver->shouldReceive('resolve')->andReturn($mockProvider);

    $transaction = (new CreateTransaction($mockResolver))->execute($sender, [
        'booking_id' => $booking->id,
        'amount'     => 15000,
        'country'    => 'MA',
        'method'     => PaymentMethodEnum::CARD->value,
        // currency absent → doit être résolu via forCountry('MA') = MAD
    ]);

    expect($transaction->currency)->toBe(CurrencyEnum::MAD);
});

it('CreateTransaction respecte la currency explicite si fournie (F-007)', function () {
    $sender  = User::factory()->create(['role' => UserRoleEnum::SENDER]);
    $traveler = User::factory()->create(['role' => UserRoleEnum::TRAVELER]);
    $trip = Trip::factory()->create(['user_id' => $traveler->id]);
    $booking = Booking::factory()->create([
        'user_id'          => $sender->id,
        'trip_id'          => $trip->id,
        'status'           => BookingStatusEnum::EN_PAIEMENT,
        'payment_expires_at' => now()->addHour(),
    ]);

    $mockProvider = \Mockery::mock(\App\Contracts\Payments\PaymentProvider::class);
    $mockProvider->shouldReceive('charge')->andReturn(new \App\Data\Payments\PaymentResponseData(
        provider: \App\Enums\PaymentProviderEnum::FAKE,
        providerTransactionId: 'fake_001',
        providerStatus: 'pending',
        amount: 10000,
        currency: CurrencyEnum::EUR,
        checkoutUrl: null,
        eventId: null,
        rawPayload: [],
    ));

    $mockResolver = \Mockery::mock(\App\Contracts\Payments\PaymentProviderResolverContract::class);
    $mockResolver->shouldReceive('resolve')->andReturn($mockProvider);

    $transaction = (new CreateTransaction($mockResolver))->execute($sender, [
        'booking_id' => $booking->id,
        'amount'     => 10000,
        'country'    => 'SN',           // pays = SN mais…
        'currency'   => CurrencyEnum::EUR, // currency explicite = EUR → respecté
        'method'     => PaymentMethodEnum::CARD->value,
    ]);

    expect($transaction->currency)->toBe(CurrencyEnum::EUR);
});
