<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentRequestData;
use RuntimeException;

final class PaymentProviderResolver
{
    public function resolve(PaymentRequestData $request): PaymentProvider
    {
        $providerKey = $this->resolveProviderKey(
            country: strtoupper(trim($request->country)),
            method: $request->method->value,
        );

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

    private function resolveProviderKey(string $country, string $method): string
    {
        $providerKey = config("payment_providers.routing.{$country}.{$method}");

        if (is_string($providerKey)) {
            return $providerKey;
        }

        $default = config('payment_providers.default');

        if (! is_string($default)) {
            throw new RuntimeException('Default payment provider is not configured.');
        }

        return $default;
    }
}
