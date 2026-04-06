<?php

use App\Actions\Payment\HandlePaymentWebhook;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('marque un refund pending comme completed et passe le booking à remboursee', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $refund = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
        'status' => TransactionStatusEnum::PENDING,
        'provider_transaction_id' => 'fake_refund_123',
        'processed_at' => null,
    ]);

    app(HandlePaymentWebhook::class)->execute([
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_123',
    ]);

    $refund->refresh();
    $booking->refresh();

    expect($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($refund->processed_at)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatusEnum::REMBOURSEE);
});

it('marque un refund pending comme failed et laisse le booking en litige', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $refund = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
        'status' => TransactionStatusEnum::PENDING,
        'provider_transaction_id' => 'fake_refund_456',
        'processed_at' => null,
    ]);

    app(HandlePaymentWebhook::class)->execute([
        'event' => 'refund.failed',
        'provider_transaction_id' => 'fake_refund_456',
    ]);

    $refund->refresh();
    $booking->refresh();

    expect($refund->status)->toBe(TransactionStatusEnum::FAILED)
        ->and($refund->processed_at)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatusEnum::EN_LITIGE);
});

it('est idempotent si le webhook refund completed est reçu deux fois', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->create();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status' => BookingStatusEnum::EN_LITIGE,
    ]);

    $refund = Transaction::factory()->create([
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'type' => TransactionTypeEnum::REFUND,
        'status' => TransactionStatusEnum::PENDING,
        'provider_transaction_id' => 'fake_refund_789',
        'processed_at' => null,
    ]);

    $payload = [
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_789',
    ];

    $action = app(HandlePaymentWebhook::class);

    $action->execute($payload);
    $action->execute($payload);

    $refund->refresh();
    $booking->refresh();

    expect($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
        ->and($booking->status)->toBe(BookingStatusEnum::REMBOURSEE);
});

it('ignore silencieusement un provider_transaction_id inconnu', function () {
    app(HandlePaymentWebhook::class)->execute([
        'event' => 'refund.completed',
        'provider_transaction_id' => 'unknown_refund_id',
    ]);

    expect(true)->toBeTrue();
});
