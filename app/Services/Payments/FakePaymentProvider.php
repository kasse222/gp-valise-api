<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use Illuminate\Support\Str;

final class FakePaymentProvider implements PaymentProvider
{

    private function guardAgainstProduction(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException(
                'FakePaymentProvider is not allowed in production.'
            );
        }
    }

    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        $this->guardAgainstProduction();

        return $this->simulate(
            type: 'charge',
            amount: $request->amount,
            currency: $request->currency,
            metadata: $request->metadata,
        );
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        $this->guardAgainstProduction();

        return $this->simulate(
            type: 'refund',
            amount: $request->amount,
            currency: $request->currency,
            metadata: [
                ...$request->metadata,
                'reason' => $request->reason,
            ],
        );
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        return true;
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;

        $rawEvent = (string) ($payload['event'] ?? $payload['event_type'] ?? 'transaction.success');

        return new PaymentEventData(
            provider: PaymentProviderEnum::FAKE,
            eventId: (string) ($payload['event_id'] ?? 'fake_evt_' . Str::uuid()),
            eventType: $rawEvent,
            providerTransactionId: (string) ($payload['provider_transaction_id'] ?? 'fake_tx_' . Str::uuid()),
            providerStatus: (string) ($payload['status'] ?? 'completed'),
            amount: (int) ($payload['amount'] ?? 0),
            currency: CurrencyEnum::from((string) ($payload['currency'] ?? 'EUR')),
            metadata: $payload['metadata'] ?? [],
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::FAKE->value;
    }

    private function simulate(
        string $type,
        int $amount,
        CurrencyEnum $currency,
        array $metadata
    ): PaymentResponseData {
        $mode = $metadata['force_status'] ?? config('payment.fake.mode', 'success');

        $status = match ($mode) {
            'failure' => 'failed',
            'pending' => 'pending',
            default => 'completed',
        };

        return new PaymentResponseData(
            provider: PaymentProviderEnum::FAKE,
            providerTransactionId: 'fake_' . $type . '_' . Str::uuid(),
            providerStatus: $status,
            amount: $amount,
            currency: $currency,
            checkoutUrl: null,
            eventId: 'fake_evt_' . Str::uuid(),
            rawPayload: [
                'type' => $type,
                'status' => $status,
                'metadata' => $metadata,
            ],
        );
    }
}
