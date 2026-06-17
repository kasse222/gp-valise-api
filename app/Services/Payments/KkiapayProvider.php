<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\KkiapayAdminClientContract;
use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;
use App\Payments\Mappers\KkiapayStatusMapper;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class KkiapayProvider implements PaymentProvider
{
    public function __construct(
        private readonly KkiapayAdminClientContract $adminClient,
        private readonly KkiapayStatusMapper        $statusMapper,
    ) {}

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
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'    => config('payment_providers.kkiapay.public_key'),
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post($this->baseUrl() . '/api/v1/transactions/initialize', $payload);

            $response->throw();
            $body = $response->json();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new RuntimeException(
                "Kkiapay charge failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        $transactionId = (string) ($body['transactionId'] ?? '');

        if ($transactionId === '') {
            throw new RuntimeException('Kkiapay charge response missing transactionId.');
        }

        return new PaymentResponseData(
            provider: PaymentProviderEnum::KKIAPAY,
            providerTransactionId: $transactionId,
            providerStatus: (string) ($body['status'] ?? 'pending'),
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: $body['paymentUrl'] ?? null,
            eventId: null,
            rawPayload: $body,
        );
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        try {
            $result = $this->adminClient->refund($request->providerTransactionId);
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Kkiapay refund failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($result === false) {
            throw new RuntimeException(
                "Kkiapay refund failed for transaction: {$request->providerTransactionId}"
            );
        }

        $rawStatus = strtolower((string) ($result['status'] ?? ''));
        $mapped    = $this->statusMapper->map($rawStatus);

        $providerStatus = match (true) {
            $mapped->isUnknown() => 'failed',
            $mapped->isSuccess() => 'completed',
            $mapped->isFailed()  => 'failed',
            default              => 'pending',
        };

        return new PaymentResponseData(
            provider: PaymentProviderEnum::KKIAPAY,
            providerTransactionId: $request->providerTransactionId,
            providerStatus: $providerStatus,
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: null,
            eventId: null,
            rawPayload: $result,
        );
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $secret = config('payment_providers.kkiapay.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Kkiapay webhook secret is not configured.');
        }

        $signature = $webhook->signature
            ?? ($webhook->headers['x-kkiapay-secret'][0]
                ?? $webhook->headers['x-kkiapay-secret']
                ?? null);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals($secret, $signature);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;

        $transactionId = (string) ($payload['transactionId'] ?? '');
        $rawEvent      = (string) ($payload['event'] ?? '');
        $amount        = (int) ($payload['amount'] ?? 0);

        if ($transactionId === '') {
            throw new RuntimeException('Kkiapay webhook missing transactionId.');
        }

        if ($rawEvent === '') {
            throw new RuntimeException('Kkiapay webhook missing event.');
        }

        // F-019 — eventId unique par événement : provider + transactionId + event
        // transactionId seul est partagé entre events d'une même transaction
        $eventId = 'kkiapay_' . $transactionId . '_' . $rawEvent;

        // Mapper branché — remplace le match() inline
        $mappedStatus = $this->statusMapper->map($rawEvent);

        if ($mappedStatus->isUnknown()) {
            Log::warning('Kkiapay webhook event inconnu — ignoré', [
                'event'          => $rawEvent,
                'transaction_id' => $transactionId,
                'payload'        => $payload,
            ]);
        }

        $providerStatus = match ($rawEvent) {
            'transaction.success' => 'completed',
            'transaction.failed'  => 'failed',
            default               => $mappedStatus->isUnknown() ? 'unknown' : 'pending',
        };

        $eventType = match ($rawEvent) {
            'transaction.success' => 'transaction.success',
            'transaction.failed'  => 'transaction.failed',
            default               => $mappedStatus->isUnknown() ? 'payment.unknown' : $rawEvent,
        };

        return new PaymentEventData(
            provider: PaymentProviderEnum::KKIAPAY,
            eventId: $eventId,
            eventType: $eventType,
            providerTransactionId: $transactionId,
            providerStatus: $providerStatus,
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

    private function baseUrl(): string
    {
        return config('payment_providers.kkiapay.sandbox', true)
            ? 'https://sandbox-api.kkiapay.me'
            : 'https://api.kkiapay.me';
    }
}
