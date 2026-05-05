<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;

final class PaymentEventData
{
    public function __construct(
        public readonly PaymentProviderEnum $provider,

        /**
         * ID unique de l’événement provider (idempotence)
         */
        public readonly string $eventId,

        /**
         * ID transaction côté provider
         */
        public readonly string $providerTransactionId,

        /**
         * Statut brut du provider (non mappé)
         */
        public readonly string $providerStatus,

        /**
         * Montant en plus petite unité
         */
        public readonly int $amount,

        public readonly CurrencyEnum $currency,

        /**
         * Metadata utile (booking_id, user_id, etc.)
         */
        public readonly array $metadata = [],

        /**
         * Payload brut complet (audit / debug)
         */
        public readonly array $rawPayload = [],
    ) {}
}
