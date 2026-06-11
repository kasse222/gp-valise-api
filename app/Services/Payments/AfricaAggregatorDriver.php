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
        try {
            $response = $this->paydunya->charge($request);
            $this->activeProvider = PaymentProviderEnum::PAYDUNYA->value;
            return $response;
        } catch (RuntimeException $e) {
            Log::warning('AfricaAggregator: PayDunya charge failed — switching to Naboopay', [
                'booking_id' => $request->metadata['booking_id'] ?? null,
                'error'      => $e->getMessage(),
            ]);
        }

        try {
            $response = $this->naboopay->charge($request);
            $this->activeProvider = PaymentProviderEnum::NABOOPAY->value;
            return $response;
        } catch (RuntimeException $e) {
            Log::error('AfricaAggregator: Naboopay charge also failed — all providers exhausted', [
                'booking_id' => $request->metadata['booking_id'] ?? null,
                'error'      => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'All Africa payment providers failed. Please try again later.',
                previous: $e,
            );
        }
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
        return $this->naboopay->ping() || $this->isPaydunyaAvailable();
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
        // PayDunya n'expose pas de health endpoint public.
        // On considère disponible par défaut — la charge révèle la vraie disponibilité.
        return true;
    }
}
