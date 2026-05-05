<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\PaymentProviderEnum;
use BadMethodCallException;
use RuntimeException;

final class KkiapayProvider implements PaymentProvider
{
    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        // Phase 2B : implémenter l'appel sandbox Kkiapay réel ici.
        throw new BadMethodCallException('Kkiapay charge is not implemented yet.');
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        // Phase 2B/2C selon la capacité refund du PSP.
        throw new BadMethodCallException('Kkiapay refund is not implemented yet.');
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $secret = config('payment_providers.kkiapay.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Kkiapay webhook secret is not configured.');
        }

        $signature = $webhook->signature
            ?? ($webhook->headers['x-kkiapay-secret'][0] ?? $webhook->headers['x-kkiapay-secret'] ?? null);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals($secret, $signature);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;

        return new PaymentEventData(
            provider: PaymentProviderEnum::KKIAPAY,
            eventId: (string) ($payload['event_id'] ?? $payload['transactionId'] ?? ''),
            providerTransactionId: (string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? ''),
            providerStatus: (string) ($payload['status'] ?? ''),
            amount: (int) ($payload['amount'] ?? 0),
            currency: $webhook->payload['currency'],
            metadata: $payload['metadata'] ?? [],
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::KKIAPAY->value;
    }
}
