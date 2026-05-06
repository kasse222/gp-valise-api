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
use BadMethodCallException;
use RuntimeException;

final class StripeProvider implements PaymentProvider
{
    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        // Phase 2C → implémentation réelle Stripe Checkout / PaymentIntent
        throw new BadMethodCallException('Stripe charge not implemented yet.');
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        // Phase 2C → implémentation refund Stripe
        throw new BadMethodCallException('Stripe refund not implemented yet.');
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $secret = config('payment_providers.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Stripe webhook secret not configured.');
        }

        $signature = $webhook->signature
            ?? ($webhook->headers['stripe-signature'][0] ?? $webhook->headers['stripe-signature'] ?? null);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        // ⚠️ Phase 2C → utiliser Stripe::Webhook::constructEvent(...)
        // ici on simule une vérification minimale
        return hash_equals($secret, $signature);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;
        $object  = $payload['data']['object'] ?? [];

        return new PaymentEventData(
            provider: PaymentProviderEnum::STRIPE,
            eventId: (string) ($payload['id'] ?? ''),
            eventType: (string) ($payload['type'] ?? ''),   // 'payment_intent.succeeded'
            providerTransactionId: (string) ($object['id'] ?? ''),
            providerStatus: (string) ($object['status'] ?? $payload['type'] ?? ''),
            amount: (int) ($object['amount'] ?? 0),
            currency: CurrencyEnum::from(strtoupper((string) ($object['currency'] ?? 'EUR'))),
            metadata: $object['metadata'] ?? [],
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::STRIPE->value;
    }
}
