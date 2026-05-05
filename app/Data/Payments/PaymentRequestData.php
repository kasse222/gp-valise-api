<?php

declare(strict_types=1);

namespace App\Data\Payments;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentOperatorEnum;

final class PaymentRequestData
{
    public function __construct(
        /**
         * Code pays ISO 3166-1 alpha-2 : SN, MA, FR...
         */
        public readonly string $country,

        public readonly CurrencyEnum $currency,

        public readonly PaymentMethodEnum $method,

        /**
         * Montant en plus petite unité :
         * - EUR/MAD : centimes
         * - XOF : unité entière
         */
        public readonly int $amount,

        /**
         * Clé d'idempotence interne.
         */
        public readonly string $idempotencyKey,

        /**
         * Obligatoire pour MOBILE_MONEY.
         * Null pour CARD.
         */
        public readonly ?PaymentOperatorEnum $operator = null,

        /**
         * Ex: booking_id, user_id, correlation_id.
         */
        public readonly array $metadata = [],
    ) {}
}
