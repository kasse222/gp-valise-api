<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CurrencyEnum;
use App\Models\PlatformAccount;
use RuntimeException;

final class PlatformAccountResolver
{
    public function resolveForCountry(string $countryCode, CurrencyEnum $currency): PlatformAccount
    {
        $account = PlatformAccount::query()
            ->where('country_code', strtoupper($countryCode))
            ->where('currency', $currency->value)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            throw new RuntimeException(
                "No active platform account for country [{$countryCode}] and currency [{$currency->value}]."
            );
        }

        return $account;
    }

    public function resolveByCurrency(CurrencyEnum $currency): PlatformAccount
    {
        $account = PlatformAccount::query()
            ->where('currency', $currency->value)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            throw new RuntimeException(
                "No active platform account for currency [{$currency->value}]."
            );
        }

        return $account;
    }

    public function resolveByProvider(string $provider, CurrencyEnum $currency): PlatformAccount
    {
        $account = PlatformAccount::query()
            ->where('provider', $provider)
            ->where('currency', $currency->value)
            ->where('is_active', true)
            ->first();

        if ($account === null) {
            throw new RuntimeException(
                "No active platform account for provider [{$provider}] and currency [{$currency->value}]."
            );
        }

        return $account;
    }
}
