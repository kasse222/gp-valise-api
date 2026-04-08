<?php

use App\Actions\Payment\HandlePaymentWebhook;
use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Jobs\ProcessPaymentWebhook;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('reçoit un webhook, dispatch le job puis le traitement met à jour refund et booking', function () {
    Queue::fake();

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
            'provider_transaction_id' => 'fake_refund_e2e_456',
        ]);

    $payload = [
        'event_id' => 'evt_e2e_456',
        'event' => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_e2e_456',
    ];

    $signature = hash_hmac(
        'sha256',
        json_encode($payload),
        config('payment.webhook.secret')
    );

    $response = $this->postJson('/api/v1/webhooks/payment', $payload, [
        'X-Signature' => $signature,
    ]);
    //appeler le controller
    $response->assertAccepted();
    //appeler le controller
    Queue::assertPushed(ProcessPaymentWebhook::class, function (ProcessPaymentWebhook $job) use ($payload, $refund, $booking) {

        //  exécuter ensuite le job manuellement
        app(HandlePaymentWebhook::class)->execute($job->payload);

        $refund->refresh();
        $booking->refresh();

        expect($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
            ->and($refund->processed_at)->not->toBeNull()
            ->and($booking->status)->toBe(BookingStatusEnum::REMBOURSEE);

        return true;
    });
});
