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
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class KkiapayProvider implements PaymentProvider
{
    private const SANDBOX_BASE_URL = 'https://sandbox-api.kkiapay.me';
    private const LIVE_BASE_URL    = 'https://api.kkiapay.me';

    private function baseUrl(): string
    {
        return config('payment_providers.kkiapay.sandbox', true)
            ? self::SANDBOX_BASE_URL
            : self::LIVE_BASE_URL;
    }

    private function apiKey(): string
    {
        $key = config('payment_providers.kkiapay.api_key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Kkiapay API key is not configured.');
        }

        return $key;
    }

    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        $meta = $request->metadata;

        $payload = [
            'amount'    => $request->amount,
            'phone'     => (string) ($meta['customer_phone'] ?? ''),
            'callback'  => (string) ($meta['callback_url'] ?? ''),
            'firstname' => (string) ($meta['customer_firstname'] ?? ''),
            'lastname'  => (string) ($meta['customer_lastname'] ?? ''),
            'email'     => (string) ($meta['customer_email'] ?? ''),
        ];

        if ($request->operator !== null) {
            $payload['payment_method'] = $request->operator->value;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'    => $this->apiKey(),
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post("{$this->baseUrl()}/api/v1/transactions/initialize", $payload);

            $response->throw();

            $body = $response->json();
        } catch (RequestException $e) {
            throw new RuntimeException(
                "Kkiapay charge failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        $transactionId = (string) ($body['transactionId'] ?? '');
        $status        = (string) ($body['status'] ?? 'pending');

        if ($transactionId === '') {
            throw new RuntimeException('Kkiapay charge response missing transactionId.');
        }

        return new PaymentResponseData(
            provider: PaymentProviderEnum::KKIAPAY,
            providerTransactionId: $transactionId,
            providerStatus: $status,
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: $body['paymentUrl'] ?? null,
            eventId: null,
            rawPayload: $body,
        );
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        throw new \BadMethodCallException('Kkiapay refund not implemented yet.');
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
    //KkiapayProvider::normalizeWebhook() pour alimenter eventType
    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;

        $transactionId = (string) ($payload['transactionId'] ?? '');
        $event         = (string) ($payload['event'] ?? '');
        $amount        = (int) ($payload['amount'] ?? 0);

        if ($transactionId === '') {
            throw new RuntimeException('Kkiapay webhook missing transactionId.');
        }

        if ($event === '') {
            throw new RuntimeException('Kkiapay webhook missing event.');
        }

        $providerStatus = match ($event) {
            'transaction.success' => 'completed',
            'transaction.failed'  => 'failed',
            default               => 'pending',
        };

        return new PaymentEventData(
            provider: PaymentProviderEnum::KKIAPAY,
            eventId: $transactionId,
            eventType: $event,               // 'transaction.success' / 'transaction.failed'
            providerTransactionId: $transactionId,
            providerStatus: $providerStatus, // 'completed' / 'failed'
            amount: $amount,
            currency: CurrencyEnum::XOF,
            metadata: (array) ($payload['stateData'] ?? []),
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::KKIAPAY->value;
    }
}
