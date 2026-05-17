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

final class PayDunyaProvider implements PaymentProvider
{
    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        $meta = $request->metadata;

        $payload = [
            'invoice' => [
                'total_amount' => $request->amount,
                'description'  => 'GP-Valise — Réservation #' . ($meta['booking_id'] ?? ''),
            ],
            'store' => [
                'name' => 'Safe Move',
            ],
            'actions' => [
                'cancel_url'  => config('payment_providers.paydunya.cancel_url'),
                'return_url'  => config('payment_providers.paydunya.success_url'),
                'callback_url' => config('payment_providers.paydunya.callback_url'),
            ],
            'custom_data' => [
                'booking_id' => $meta['booking_id'] ?? null,
                'user_id'    => $meta['user_id'] ?? null,
            ],
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post($this->baseUrl() . '/checkout-invoice/create', $payload);

            $response->throw();
            $body = $response->json();
        } catch (\Exception $e) {
            throw new RuntimeException("PayDunya charge failed: {$e->getMessage()}", previous: $e);
        }

        $token = (string) ($body['token'] ?? '');

        if ($token === '') {
            throw new RuntimeException('PayDunya charge response missing token.');
        }

        $checkoutUrl = $this->isSandbox()
            ? "https://app.paydunya.com/sandbox/checkout/invoice/{$token}"
            : "https://app.paydunya.com/checkout/invoice/{$token}";

        \Illuminate\Support\Facades\Log::debug('PayDunya response', $body);
        return new PaymentResponseData(
            provider: PaymentProviderEnum::PAYDUNYA,
            providerTransactionId: $token,
            providerStatus: 'pending',
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: $checkoutUrl,
            eventId: null,
            rawPayload: $body,
        );
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        // PayDunya ne supporte pas le refund API automatique
        throw new RuntimeException('PayDunya refund must be processed manually via dashboard.');
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $payload = $webhook->payload;
        $hash    = (string) ($payload['hash'] ?? '');

        if ($hash === '') {
            return false;
        }

        $masterKey = (string) config('payment_providers.paydunya.master_key');

        return hash_equals(sha1($masterKey), $hash);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $webhook->payload;
        $status  = strtolower((string) ($payload['status'] ?? ''));
        $token   = (string) ($payload['token'] ?? '');

        if ($token === '') {
            throw new RuntimeException('PayDunya webhook missing token.');
        }

        $providerStatus = match ($status) {
            'completed' => 'completed',
            'cancelled', 'failed' => 'failed',
            default => 'pending',
        };

        $eventType = match ($providerStatus) {
            'completed' => 'transaction.success',
            'failed'    => 'transaction.failed',
            default     => 'transaction.pending',
        };

        $amount = (int) ($payload['invoice']['total_amount'] ?? 0);

        return new PaymentEventData(
            provider: PaymentProviderEnum::PAYDUNYA,
            eventId: $token,
            eventType: $eventType,
            providerTransactionId: $token,
            providerStatus: $providerStatus,
            amount: $amount,
            currency: CurrencyEnum::XOF,
            metadata: (array) ($payload['custom_data'] ?? []),
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::PAYDUNYA->value;
    }

    private function headers(): array
    {
        return [
            'PAYDUNYA-MASTER-KEY'  => config('payment_providers.paydunya.master_key'),
            'PAYDUNYA-PRIVATE-KEY' => config('payment_providers.paydunya.private_key'),
            'PAYDUNYA-TOKEN'       => config('payment_providers.paydunya.token'),
            'Content-Type'         => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://app.paydunya.com/sandbox-api/v1'
            : 'https://app.paydunya.com/api/v1';
    }

    private function isSandbox(): bool
    {
        return (bool) config('payment_providers.paydunya.sandbox', true);
    }
}
