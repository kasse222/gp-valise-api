<?php

declare(strict_types=1);

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
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('reçoit un webhook, dispatch le job puis le traitement met à jour refund et booking', function (): void {
    Queue::fake();

    $user    = User::factory()->create();
    $trip    = Trip::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'status'  => BookingStatusEnum::EN_LITIGE,
    ]);
    $refund = Transaction::factory()
        ->refund()
        ->pending()
        ->create([
            'user_id'                 => $user->id,
            'booking_id'              => $booking->id,
            'provider_transaction_id' => 'fake_refund_e2e_456',
        ]);

    // Payload brut FakeProvider
    $rawPayload = [
        'event_id'                => 'evt_e2e_456',
        'event'                   => 'refund.completed',
        'provider_transaction_id' => 'fake_refund_e2e_456',
        'status'                  => 'completed',
        'amount'                  => 1000,
        'currency'                => 'EUR',
    ];

    $correlationId = (string) Str::uuid();

    $response = $this->postJson('/api/v1/webhooks/fake', $rawPayload, [
        'X-Correlation-ID' => $correlationId,
    ]);

    $response->assertAccepted();

    Queue::assertPushed(
        ProcessPaymentWebhook::class,
        function (ProcessPaymentWebhook $job) use ($refund, $booking, $correlationId): bool {
            expect($job->correlationId)->toBe($correlationId)
                ->and($job->payload['event_id'])->toBe('evt_e2e_456')
                ->and($job->payload['event_type'])->toBe('refund.completed')
                ->and($job->payload['provider'])->toBe('fake')
                ->and($job->payload['provider_transaction_id'])->toBe('fake_refund_e2e_456');

            app(HandlePaymentWebhook::class)->execute($job->payload, $job->correlationId);

            $refund->refresh();
            $booking->refresh();

            expect($refund->status)->toBe(TransactionStatusEnum::COMPLETED)
                ->and($refund->processed_at)->not->toBeNull()
                ->and($booking->status)->toBe(BookingStatusEnum::REMBOURSEE);

            return true;
        }
    );
});
