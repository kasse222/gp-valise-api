<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;

final class PaymentEventData
{
    public function __construct(
        public readonly PaymentProviderEnum $provider,
        public readonly string $eventId,
        public readonly string $eventType,          // ← NOUVEAU : vocabulaire domaine
        public readonly string $providerTransactionId,
        public readonly string $providerStatus,     // statut brut PSP
        public readonly int $amount,
        public readonly CurrencyEnum $currency,
        public readonly array $metadata = [],
        public readonly array $rawPayload = [],
    ) {}
}
