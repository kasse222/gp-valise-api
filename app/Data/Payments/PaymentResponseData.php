<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;

final class PaymentResponseData
{
    public function __construct(
        /**
         * Provider ayant traité la requête (kkiapay, stripe, …)
         */
        public readonly PaymentProviderEnum $provider,

        /**
         * ID transaction côté provider
         */
        public readonly string $providerTransactionId,

        /**
         * Statut BRUT du provider (ex: success, pending, failed, succeeded…)
         * → sera mappé ailleurs vers PaymentStatusEnum
         */
        public readonly string $providerStatus,

        /**
         * Montant en plus petite unité
         */
        public readonly int $amount,

        public readonly CurrencyEnum $currency,

        /**
         * URL de paiement / checkout (si applicable)
         */
        public readonly ?string $checkoutUrl = null,

        /**
         * ID événement (utile pour certains flows async)
         */
        public readonly ?string $eventId = null,

        /**
         * Payload brut provider (debug / audit / replay)
         */
        public readonly array $rawPayload = [],
    ) {}
}
