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
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Naboopay — agrégateur West Africa (Wave, Orange Money, Free Money)
 *
 * Doc : https://docs.naboopay.com
 * Auth : Bearer token (API key)
 * Webhook : HMAC-SHA256 sur le raw body avec x-naboopay-signature header
 */
final class NaboopayProvider implements PaymentProvider
{
    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        $meta = $request->metadata;

        $payload = [
            'amount'       => $request->amount,
            'currency'     => $request->currency->value,
            'order_id'     => $request->idempotencyKey,
            'description'  => 'SafeMove — Réservation #' . ($meta['booking_id'] ?? ''),
            'success_url'  => config('payment_providers.naboopay.success_url')
                . '?booking_id=' . ($meta['booking_id'] ?? ''),
            'cancel_url'   => config('payment_providers.naboopay.cancel_url')
                . '?booking_id=' . ($meta['booking_id'] ?? ''),
            'callback_url' => config('payment_providers.naboopay.callback_url'),
            'customer'     => [
                'phone' => (string) ($meta['customer_phone'] ?? ''),
                'email' => (string) ($meta['customer_email'] ?? ''),
                'name'  => trim(
                    ($meta['customer_firstname'] ?? '') . ' ' .
                        ($meta['customer_lastname'] ?? '')
                ),
            ],
            'metadata' => [
                'booking_id' => $meta['booking_id'] ?? null,
                'user_id'    => $meta['user_id'] ?? null,
            ],
        ];

        if ($request->operator !== null) {
            $payload['payment_method'] = $request->operator->value;
        }

        try {
            $response = Http::withToken($this->apiKey())
                ->timeout(15)
                ->post($this->baseUrl() . '/transactions/create', $payload);

            $response->throw();
            $body = $response->json();
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Naboopay charge failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        $transactionId = (string) ($body['transaction_id'] ?? $body['id'] ?? '');

        if ($transactionId === '') {
            throw new RuntimeException('Naboopay charge response missing transaction_id.');
        }

        $checkoutUrl = (string) ($body['payment_url'] ?? $body['checkout_url'] ?? '');

        if ($checkoutUrl === '') {
            throw new RuntimeException('Naboopay charge response missing payment_url.');
        }

        return new PaymentResponseData(
            provider: PaymentProviderEnum::NABOOPAY,
            providerTransactionId: $transactionId,
            providerStatus: (string) ($body['status'] ?? 'pending'),
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: $checkoutUrl,
            eventId: null,
            rawPayload: $body,
        );
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        $payload = [
            'transaction_id' => $request->providerTransactionId,
            'amount'         => $request->amount,
            'reason'         => $request->reason ?? 'requested_by_customer',
        ];

        try {
            $response = Http::withToken($this->apiKey())
                ->timeout(15)
                ->post($this->baseUrl() . '/transactions/refund', $payload);

            $response->throw();
            $body = $response->json();
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Naboopay refund failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        $status = match (strtolower((string) ($body['status'] ?? ''))) {
            'success', 'successful', 'completed' => 'completed',
            'pending', 'processing'              => 'pending',
            default                              => 'failed',
        };

        return new PaymentResponseData(
            provider: PaymentProviderEnum::NABOOPAY,
            providerTransactionId: $request->providerTransactionId,
            providerStatus: $status,
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: null,
            eventId: null,
            rawPayload: $body,
        );
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $secret = config('payment_providers.naboopay.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Naboopay webhook secret is not configured.');
        }

        $signature = $webhook->signature
            ?? ($webhook->headers['x-naboopay-signature'][0]
                ?? $webhook->headers['x-naboopay-signature']
                ?? null);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $webhook->rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;

        $transactionId = (string) ($payload['transaction_id'] ?? $payload['id'] ?? '');
        $event         = (string) ($payload['event'] ?? $payload['event_type'] ?? '');
        $amount        = (int) ($payload['amount'] ?? 0);
        $currency      = strtoupper((string) ($payload['currency'] ?? 'XOF'));

        if ($transactionId === '') {
            throw new RuntimeException('Naboopay webhook missing transaction_id.');
        }

        if ($event === '') {
            throw new RuntimeException('Naboopay webhook missing event.');
        }

        $providerStatus = match ($event) {
            'transaction.success', 'payment.success', 'payment.completed' => 'completed',
            'transaction.failed',  'payment.failed'                        => 'failed',
            'refund.completed'                                              => 'completed',
            'refund.failed'                                                 => 'failed',
            default                                                         => 'pending',
        };

        $eventType = match ($event) {
            'transaction.success', 'payment.success', 'payment.completed' => 'transaction.success',
            'transaction.failed',  'payment.failed'                        => 'transaction.failed',
            'refund.completed'                                              => 'refund.completed',
            'refund.failed'                                                 => 'refund.failed',
            default                                                         => $event,
        };

        $currencyEnum = CurrencyEnum::tryFrom($currency) ?? CurrencyEnum::XOF;

        return new PaymentEventData(
            provider: PaymentProviderEnum::NABOOPAY,
            eventId: (string) ($payload['event_id'] ?? $transactionId),
            eventType: $eventType,
            providerTransactionId: $transactionId,
            providerStatus: $providerStatus,
            amount: $amount,
            currency: $currencyEnum,
            metadata: (array) ($payload['metadata'] ?? []),
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::NABOOPAY->value;
    }

    /**
     * Ping l'API pour vérifier la disponibilité.
     * Utilisé par AfricaAggregatorDriver pour le health check.
     */
    public function ping(): bool
    {
        try {
            $response = Http::withToken($this->apiKey())
                ->timeout(5)
                ->get($this->baseUrl() . '/health');

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    private function apiKey(): string
    {
        $key = config('payment_providers.naboopay.api_key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Naboopay API key is not configured.');
        }

        return $key;
    }

    private function baseUrl(): string
    {
        return (bool) config('payment_providers.naboopay.sandbox', true)
            ? 'https://api.naboopay.com/sandbox/v1'
            : 'https://api.naboopay.com/v1';
    }
}
