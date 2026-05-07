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
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

final class StripeProvider implements PaymentProvider
{
    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        try {
            $payload = [
                'mode' => 'payment',

                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($request->currency->value),
                            'unit_amount' => $request->amount,
                            'product_data' => [
                                'name' => 'GP-Valise — Réservation bagage',
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],

                'success_url' => $this->successUrl(),
                'cancel_url' => $this->cancelUrl(),

                'client_reference_id' => $request->idempotencyKey,

                'metadata' => $this->normalizeMetadata($request->metadata),

                'payment_intent_data' => [
                    'metadata' => $this->normalizeMetadata($request->metadata),
                ],

                'expand' => ['payment_intent'],
            ];

            $customerEmail = $request->metadata['customer_email'] ?? null;

            if (is_string($customerEmail) && $customerEmail !== '') {
                $payload['customer_email'] = $customerEmail;
            }

            $session = $this->client()
                ->checkout
                ->sessions
                ->create(
                    $payload,
                    ['idempotency_key' => $request->idempotencyKey]
                );
        } catch (ApiErrorException $e) {
            throw new RuntimeException(
                "Stripe charge failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        $paymentIntentId = $this->extractPaymentIntentId($session);

        if ($paymentIntentId === '') {
            throw new RuntimeException('Stripe checkout session missing payment_intent.');
        }

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe checkout session missing checkout URL.');
        }

        return new PaymentResponseData(
            provider: PaymentProviderEnum::STRIPE,
            providerTransactionId: $paymentIntentId,
            providerStatus: 'pending',
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: $session->url,
            eventId: (string) $session->id,
            rawPayload: $session->toArray(),
        );
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        try {
            $refund = $this->client()->refunds->create(
                [
                    'payment_intent' => $request->providerTransactionId,
                    'amount' => $request->amount,
                    'reason' => 'requested_by_customer',
                    'metadata' => $this->normalizeMetadata($request->metadata),
                ],
                ['idempotency_key' => $request->idempotencyKey],
            );
        } catch (ApiErrorException $e) {
            throw new RuntimeException(
                "Stripe refund failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        return new PaymentResponseData(
            provider: PaymentProviderEnum::STRIPE,
            providerTransactionId: $request->providerTransactionId,
            providerStatus: (string) $refund->status,
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: null,
            eventId: (string) $refund->id,
            rawPayload: $refund->toArray(),
        );
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $signature = $webhook->signature
            ?? ($webhook->headers['stripe-signature'][0]
                ?? $webhook->headers['stripe-signature']
                ?? null);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        try {
            Webhook::constructEvent(
                $webhook->rawBody,
                $signature,
                $this->webhookSecret(),
            );

            return true;
        } catch (SignatureVerificationException | UnexpectedValueException) {
            return false;
        }
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;
        $object = $payload['data']['object'] ?? null;
        $stripeEventType = (string) ($payload['type'] ?? '');

        if (! is_array($object) || $stripeEventType === '') {
            throw new RuntimeException('Stripe webhook payload is invalid.');
        }

        $eventType = $this->mapEventType($stripeEventType, $object);
        $providerStatus = $this->mapProviderStatus($stripeEventType, $object);
        $providerTransactionId = $this->extractProviderTransactionIdFromWebhook($stripeEventType, $object);
        $currency = $this->extractCurrency($object);
        $amount = $this->extractAmount($stripeEventType, $object);

        if ($providerTransactionId === '') {
            throw new RuntimeException('Stripe webhook missing provider transaction id.');
        }

        return new PaymentEventData(
            provider: PaymentProviderEnum::STRIPE,
            eventId: (string) ($payload['id'] ?? ''),
            eventType: $eventType,
            providerTransactionId: $providerTransactionId,
            providerStatus: $providerStatus,
            amount: $amount,
            currency: $currency,
            metadata: (array) ($object['metadata'] ?? []),
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::STRIPE->value;
    }

    private function client(): StripeClient
    {
        $apiKey = config('payment_providers.stripe.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Stripe API key is not configured.');
        }

        return new StripeClient($apiKey);
    }

    private function webhookSecret(): string
    {
        $secret = config('payment_providers.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        return $secret;
    }

    private function successUrl(): string
    {
        $url = config('payment_providers.stripe.success_url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Stripe success URL is not configured.');
        }

        return $url;
    }

    private function cancelUrl(): string
    {
        $url = config('payment_providers.stripe.cancel_url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Stripe cancel URL is not configured.');
        }

        return $url;
    }

    private function normalizeMetadata(array $metadata): array
    {
        $normalized = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $normalized[$key] = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $normalized;
    }

    private function extractPaymentIntentId(Session $session): string
    {
        if (is_string($session->payment_intent)) {
            return $session->payment_intent;
        }

        if (is_object($session->payment_intent) && isset($session->payment_intent->id)) {
            return (string) $session->payment_intent->id;
        }

        return '';
    }

    private function mapEventType(string $stripeEventType, array $object): string
    {
        return match ($stripeEventType) {
            'payment_intent.succeeded' => 'transaction.success',
            'payment_intent.payment_failed' => 'transaction.failed',
            'charge.refunded' => 'refund.completed',
            'refund.failed' => 'refund.failed',

            'refund.updated' => match ((string) ($object['status'] ?? '')) {
                'succeeded' => 'refund.completed',
                'failed', 'canceled' => 'refund.failed',
                default => 'refund.pending',
            },

            default => $stripeEventType,
        };
    }

    private function mapProviderStatus(string $stripeEventType, array $object): string
    {
        return match ($stripeEventType) {
            'payment_intent.succeeded' => 'completed',
            'payment_intent.payment_failed' => 'failed',
            'charge.refunded' => 'completed',
            'refund.failed' => 'failed',
            default => (string) ($object['status'] ?? $stripeEventType),
        };
    }

    private function extractProviderTransactionIdFromWebhook(string $stripeEventType, array $object): string
    {
        return match ($stripeEventType) {
            'charge.refunded' => (string) ($object['payment_intent'] ?? ''),
            'refund.updated', 'refund.failed' => (string) ($object['payment_intent'] ?? ''),
            default => (string) ($object['id'] ?? ''),
        };
    }

    private function extractCurrency(array $object): CurrencyEnum
    {
        $currency = CurrencyEnum::tryFrom(
            strtoupper((string) ($object['currency'] ?? 'EUR'))
        );

        if (! $currency) {
            throw new RuntimeException('Unsupported Stripe currency.');
        }

        return $currency;
    }

    private function extractAmount(string $stripeEventType, array $object): int
    {
        return match ($stripeEventType) {
            'charge.refunded' => (int) ($object['amount_refunded'] ?? 0),
            'refund.updated', 'refund.failed' => (int) ($object['amount'] ?? 0),
            default => (int) ($object['amount'] ?? $object['amount_received'] ?? 0),
        };
    }
}
