<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\AggregatorDriver;
use App\Data\Payments\PaymentRequestData;
use App\Data\Payments\PaymentResponseData;
use App\Data\Payments\RefundRequestData;
use App\Enums\PaymentProviderEnum;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AfricaAggregatorDriver
 *
 * Gère le routing multi-PSP pour le corridor West Africa.
 *
 * Primaire  : PayDunya (SN/XOF — Orange Money, Wave, Free Money)
 * Fallback  : NaboopayProvider (Wave, Orange Money, Free Money)
 *
 * Flow :
 *   1. Tente la charge via PayDunya
 *   2. Si PayDunya lève une RuntimeException → bascule sur Naboopay
 *   3. Log chaque basculement pour monitoring
 *
 * Phase 2 : circuit breaker basé sur taux d'échec réels.
 */
final class AfricaAggregatorDriver implements AggregatorDriver
{
    private string $activeProvider;

    public function __construct(
        private readonly PayDunyaProvider  $paydunya,
        private readonly NaboopayProvider  $naboopay,
    ) {
        $this->activeProvider = PaymentProviderEnum::PAYDUNYA->value;
    }

    public function charge(PaymentRequestData $request): PaymentResponseData
    {
        if ($this->isPaydunyaAvailable()) {
            $this->activeProvider = PaymentProviderEnum::PAYDUNYA->value;
            return $this->paydunya->charge($request);
        }

        Log::warning('AfricaAggregator: PayDunya indisponible — basculement sur Naboopay', [
            'booking_id' => $request->metadata['booking_id'] ?? null,
        ]);

        if ($this->isNaboopayAvailable()) {
            $this->activeProvider = PaymentProviderEnum::NABOOPAY->value;
            return $this->naboopay->charge($request);
        }

        Log::error('AfricaAggregator: tous les providers Africa indisponibles', [
            'booking_id' => $request->metadata['booking_id'] ?? null,
        ]);

        throw new RuntimeException('No Africa payment provider available.');
    }


    public function refund(RefundRequestData $request): PaymentResponseData
    {
        // Le refund doit passer par le même provider que la charge originale.
        // Le provider d'origine est stocké dans Transaction.provider.
        $providerKey = $request->metadata['original_provider'] ?? PaymentProviderEnum::PAYDUNYA->value;

        return match ($providerKey) {
            PaymentProviderEnum::NABOOPAY->value => $this->naboopay->refund($request),
            default                              => $this->paydunya->refund($request),
        };
    }

    public function isAvailable(): bool
    {
        return $this->isNaboopayAvailable() || $this->isPaydunyaAvailable();
    }

    public function getProviders(): array
    {
        return [
            PaymentProviderEnum::PAYDUNYA->value,
            PaymentProviderEnum::NABOOPAY->value,
        ];
    }

    public function getActiveProvider(): string
    {
        return $this->activeProvider;
    }


    private function isPaydunyaAvailable(): bool
    {
        // Phase 1 : PayDunya primaire forcé.
        // Phase 2 : remplacer par circuit breaker basé sur taux d'échec réels.
        return (bool) config('payment_providers.paydunya.enabled', true);
    }

    private function isNaboopayAvailable(): bool
    {
        // Naboopay désactivé par défaut — activable sans redéploiement via NABOOPAY_ENABLED=true
        return (bool) config('payment_providers.naboopay.enabled', false)
            && $this->naboopay->ping();
    }
}
