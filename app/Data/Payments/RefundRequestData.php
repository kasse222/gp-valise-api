<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentProviderEnum;

final class RefundRequestData
{
    public function __construct(
        /**
         * Provider utilisé pour effectuer le remboursement
         */
        public readonly PaymentProviderEnum $provider,

        /**
         * ID de la transaction à rembourser côté provider
         */
        public readonly string $providerTransactionId,

        /**
         * Montant à rembourser (plus petite unité)
         */
        public readonly int $amount,

        public readonly CurrencyEnum $currency,

        /**
         * Clé d'idempotence pour éviter double refund
         */
        public readonly string $idempotencyKey,

        /**
         * Raison du remboursement (audit obligatoire)
         */
        public readonly string $reason,

        /**
         * Metadata (booking_id, user_id, admin_id…)
         */
        public readonly array $metadata = [],
    ) {}
}
