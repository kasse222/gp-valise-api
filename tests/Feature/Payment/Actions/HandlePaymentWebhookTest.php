<?php

use App\Actions\Payment\HandlePaymentWebhook;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('marque un refund pending comme completed, passe le booking à remboursee et crée un log processed', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $refund = Transaction::factory()
        ->refund()
        ->pending()
        ->create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'provider_transaction_id' => 'fake_refund_123',
        ]);

    $payload = [
        'event_id' => 'evt_123',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_123',
    ];

    app(HandlePaymentWebhook::class)->execute($payload);

    $refund->refresh();
    $booking->refresh();

    $log = WebhookLog::where('event_id', 'evt_123')->first();

    expect($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($refund->processed_at)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatusEnum::REMBOURSEE)
        ->and($log)->not->toBeNull()
        ->and($log->status)->toBe(WebhookLog::STATUS_PROCESSED)
        ->and($log->event)->toBe('refund.completed')
        ->and($log->provider_transaction_id)->toBe('fake_refund_123')
        ->and($log->processed_at)->not->toBeNull();
});

it('marque un refund pending comme failed, laisse le booking en litige et crée un log processed', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $refund = Transaction::factory()
        ->refund()
        ->pending()
        ->create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'provider_transaction_id' => 'fake_refund_456',
        ]);

    $payload = [
        'event_id' => 'evt_456',
        'event' => 'refund.failed',
        'provider_transaction_id' => 'fake_refund_456',
    ];

    app(HandlePaymentWebhook::class)->execute($payload);

    $refund->refresh();
    $booking->refresh();

    $log = WebhookLog::where('event_id', 'evt_456')->first();

    expect($refund->status)->toBe(TransactionStatusEnum::FAILED)
        ->and($refund->processed_at)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatusEnum::EN_LITIGE)
        ->and($log)->not->toBeNull()
        ->and($log->status)->toBe(WebhookLog::STATUS_PROCESSED)
        ->and($log->processed_at)->not->toBeNull();
});

it('est idempotent si le même event_id est reçu deux fois', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $refund = Transaction::factory()
        ->refund()
        ->pending()
        ->create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'provider_transaction_id' => 'fake_refund_789',
        ]);

    $payload = [
        'event_id' => 'evt_789',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_789',
    ];

    $action = app(HandlePaymentWebhook::class);

    $action->execute($payload);
    $action->execute($payload);

    $refund->refresh();
    $booking->refresh();

    expect($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($booking->status)->toBe(BookingStatusEnum::REMBOURSEE)
        ->and(WebhookLog::where('event_id', 'evt_789')->count())->toBe(1);
});

it('lève une exception retryable si la transaction est introuvable', function () {
    $payload = [
        'event_id' => 'evt_retry_missing_tx',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'missing_tx_123',
    ];

    $this->expectException(\App\Exceptions\RetryableWebhookException::class);

    app(HandlePaymentWebhook::class)->execute($payload);
});

it('ignore un event non supporté et crée un log ignored', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    Transaction::factory()
        ->refund()
        ->pending()
        ->create([
            'user_id' => $user->id,
            'provider_transaction_id' => 'fake_refund_unsupported',
        ]);

    $payload = [
        'event_id' => 'evt_unsupported',
        'event' => 'refund.processing',
        'provider_transaction_id' => 'fake_refund_unsupported',
    ];

    app(HandlePaymentWebhook::class)->execute($payload);

    $log = WebhookLog::where('event_id', 'evt_unsupported')->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe(WebhookLog::STATUS_IGNORED)
        ->and($log->processed_at)->not->toBeNull();
});

it('ignore une transaction qui nest pas un refund et crée un log ignored', function () {
    $user = User::factory()->create();

    Transaction::factory()
        ->charge()
        ->pending()
        ->create([
            'user_id' => $user->id,
            'provider_transaction_id' => 'fake_charge_123',
        ]);

    $payload = [
        'event_id' => 'evt_not_refund',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_charge_123',
    ];

    app(HandlePaymentWebhook::class)->execute($payload);

    $log = WebhookLog::where('event_id', 'evt_not_refund')->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe(WebhookLog::STATUS_IGNORED)
        ->and($log->processed_at)->not->toBeNull();
});

it('ignore un payload incomplet sans créer de log', function () {
    app(HandlePaymentWebhook::class)->execute([
        'event' => 'refund.completed',
    ]);

    expect(WebhookLog::count())->toBe(0);
});
