<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\AggregatorDriver;
use App\Contracts\Payments\PaymentProvider;
use App\Contracts\Payments\PaymentProviderResolverContract;
use App\Data\Payments\PaymentRequestData;
use App\Enums\PaymentProviderEnum;
use RuntimeException;

/**
 * Résout le provider PSP selon le corridor (country + method).
 *
 * Deux types de providers sont supportés :
 *  - PaymentProvider     : provider direct (Stripe, Kkiapay, Fake…)
 *  - AggregatorDriver    : agrégateur multi-PSP avec failover (AfricaAggregatorDriver)
 *
 * Le resolver retourne toujours un PaymentProvider — si l'agrégateur est résolu,
 * il est wrappé dans un adaptateur qui expose l'interface PaymentProvider.
 *
 * F-020 : AfricaAggregatorDriver est maintenant routé via la clé 'africa_aggregator'.
 */
final class PaymentProviderResolver implements PaymentProviderResolverContract
{
    // Clé réservée pour l'agrégateur Africa
    private const AFRICA_AGGREGATOR_KEY = 'africa_aggregator';

    public function resolve(PaymentRequestData $request): PaymentProvider
    {
        $providerKey = $this->resolveProviderKey(
            country: strtoupper(trim($request->country)),
            method: $request->method->value,
        );

        $this->guardFakeInProduction($providerKey);

        return $this->instantiateProvider($providerKey);
    }

    public function resolveByKey(string $providerKey): PaymentProvider
    {
        $this->guardFakeInProduction($providerKey);

        return $this->instantiateProvider($providerKey);
    }

    // ── Résolution interne ────────────────────────────────────────────────

    private function resolveProviderKey(string $country, string $method): string
    {
        // 1. Routing explicite country + method
        $providerKey = config("payment_providers.routing.{$country}.{$method}");

        if (is_string($providerKey)) {
            return $providerKey;
        }

        // 2. Fallback default
        $default = config('payment_providers.default');

        if (! is_string($default)) {
            throw new RuntimeException('Default payment provider is not configured.');
        }

        return $default;
    }

    private function instantiateProvider(string $providerKey): PaymentProvider
    {
        // F-020 — clé spéciale : résoudre AfricaAggregatorDriver
        if ($providerKey === self::AFRICA_AGGREGATOR_KEY) {
            return $this->resolveAggregator();
        }

        $providerClass = config("payment_providers.providers.{$providerKey}");

        if (! is_string($providerClass) || ! class_exists($providerClass)) {
            throw new RuntimeException("Payment provider [{$providerKey}] is not configured.");
        }

        $provider = app($providerClass);

        if (! $provider instanceof PaymentProvider) {
            throw new RuntimeException("Payment provider [{$providerKey}] must implement PaymentProvider.");
        }

        return $provider;
    }

    /**
     * F-020 — résout AfricaAggregatorDriver et l'adapte en PaymentProvider.
     *
     * AfricaAggregatorDriver implémente AggregatorDriver (charge + refund + failover)
     * mais pas PaymentProvider (verifyWebhook + normalizeWebhook).
     *
     * Les webhooks Africa arrivent via le provider primaire (PayDunya) ou fallback (Naboopay)
     * → ils sont traités directement par leur provider, pas par l'agrégateur.
     * → resolveByKey('paydunya') ou resolveByKey('naboopay') pour les webhooks.
     */
    private function resolveAggregator(): PaymentProvider
    {
        $aggregator = app(AfricaAggregatorDriver::class);

        if (! $aggregator instanceof AggregatorDriver) {
            throw new RuntimeException('AfricaAggregatorDriver must implement AggregatorDriver.');
        }

        // AfricaAggregatorDriver expose charge() et refund() compatibles PaymentProvider.
        // verifyWebhook() et normalizeWebhook() sont délégués au provider actif.
        return new AggregatorPaymentProviderAdapter($aggregator, $this);
    }

    private function guardFakeInProduction(string $providerKey): void
    {
        if ($providerKey === PaymentProviderEnum::FAKE->value && app()->environment('production')) {
            throw new RuntimeException('FakePaymentProvider is not allowed in production.');
        }
    }
}
