<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\AggregatorDriver;
use App\Contracts\Payments\PaymentProvider;
use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentEventData;
use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Data\Payments\WebhookVerificationData;

/**
 * Adapte AfricaAggregatorDriver vers l'interface PaymentProvider.
 *
 * charge() et refund() sont délégués à l'agrégateur (qui gère le failover).
 * verifyWebhook() et normalizeWebhook() sont délégués au provider actif
 * (PayDunya ou Naboopay selon qui a traité la charge).
 *
 * Ce pattern évite de coupler la logique de failover avec la logique webhook.
 */
final class AggregatorPaymentProviderAdapter implements PaymentProvider
{
    public function __construct(
        private readonly AggregatorDriver              $aggregator,
        private readonly PaymentProviderResolverContract $resolver,
    ) {}

    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        return $this->aggregator->charge($request);
    }

    public function refund(RefundRequestData $request): PaymentResponseData
    {
        return $this->aggregator->refund($request);
    }

    public function verifyWebhook(WebhookVerificationData $webhook): bool
    {
        // Le webhook arrive toujours du provider qui a traité la charge.
        // On délègue au provider actif de l'agrégateur.
        return $this->activeProvider()->verifyWebhook($webhook);
    }

    public function normalizeWebhook(WebhookVerificationData $webhook): PaymentEventData
    {
        return $this->activeProvider()->normalizeWebhook($webhook);
    }

    public function name(): string
    {
        return 'africa_aggregator';
    }

    private function activeProvider(): PaymentProvider
    {
        $key = $this->aggregator->getActiveProvider();

        return $this->resolver->resolveByKey($key);
    }
}
