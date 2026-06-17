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
use App\Payments\Mappers\PayDunyaStatusMapper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class PayDunyaProvider implements PaymentProvider
{
    public function __construct(
        private readonly PayDunyaStatusMapper $statusMapper,
    ) {}

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
                'cancel_url'   => config('payment_providers.paydunya.cancel_url') . '?booking_id=' . ($meta['booking_id'] ?? ''),
                'return_url'   => config('payment_providers.paydunya.success_url') . '?booking_id=' . ($meta['booking_id'] ?? ''),
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
        } catch (\Throwable $e) {
            throw new RuntimeException("PayDunya charge failed: {$e->getMessage()}", previous: $e);
        }

        $token = (string) ($body['token'] ?? '');

        if ($token === '') {
            throw new RuntimeException('PayDunya charge response missing token.');
        }

        $checkoutUrl = $this->isSandbox()
            ? "https://app.paydunya.com/sandbox/checkout/invoice/{$token}"
            : "https://app.paydunya.com/checkout/invoice/{$token}";

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
        return new PaymentResponseData(
            provider: PaymentProviderEnum::PAYDUNYA,
            providerTransactionId: $request->providerTransactionId,
            providerStatus: 'pending_manual',
            amount: $request->amount,
            currency: $request->currency,
            checkoutUrl: null,
            eventId: 'manual-refund-' . $request->idempotencyKey,
            rawPayload: [
                'manual_required' => true,
                'provider'        => PaymentProviderEnum::PAYDUNYA->value,
                'reason'          => $request->reason,
            ],
        );
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        $payload  = $this->extractPayload($webhook->payload);
        $hash     = (string) ($payload['hash'] ?? '');

        if ($hash === '') {
            return false;
        }

        $masterKey = (string) config('payment_providers.paydunya.master_key');

        return hash_equals(hash('sha512', $masterKey), $hash);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        $payload = $this->extractPayload($webhook->payload);

        $rawStatus = strtolower((string) ($payload['status'] ?? ''));
        $invoice   = (array) ($payload['invoice'] ?? []);
        $token     = (string) ($payload['token'] ?? $invoice['token'] ?? '');

        if ($token === '') {
            throw new RuntimeException('PayDunya webhook missing token.');
        }

        // F-019 — eventId unique par événement : provider + token + status
        // Le token seul n'est pas unique (pending et completed partagent le même token)
        $eventId = 'paydunya_' . $token . '_' . $rawStatus;

        // Mapper branché — plus de match() inline
        $mappedStatus = $this->statusMapper->map($rawStatus);

        // Statut inconnu — loggué, aucune transition métier (conseil Pavel)
        if ($mappedStatus->isUnknown()) {
            Log::warning('PayDunya webhook status inconnu — ignoré', [
                'raw_status' => $rawStatus,
                'token'      => $token,
                'payload'    => $payload,
            ]);
        }

        $eventType = match ($rawStatus) {
            'completed'            => 'transaction.success',
            'cancelled', 'canceled',
            'failed', 'expired',
            'rejected'             => 'transaction.failed',
            default                => 'transaction.pending',
        };

        return new PaymentEventData(
            provider: PaymentProviderEnum::PAYDUNYA,
            eventId: $eventId,
            eventType: $eventType,
            providerTransactionId: $token,
            providerStatus: $mappedStatus->value !== 99 ? $rawStatus : 'unknown',
            amount: (int) ($invoice['total_amount'] ?? 0),
            currency: CurrencyEnum::XOF,
            metadata: (array) ($payload['custom_data'] ?? []),
            rawPayload: $payload,
        );
    }

    public function name(): string
    {
        return PaymentProviderEnum::PAYDUNYA->value;
    }

    private function extractPayload(array $payload): array
    {
        return isset($payload['data']) && is_array($payload['data'])
            ? $payload['data']
            : $payload;
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
