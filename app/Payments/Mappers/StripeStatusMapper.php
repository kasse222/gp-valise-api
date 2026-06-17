<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;

/**
 * Mappe les statuts Stripe vers les statuts internes canoniques.
 *
 * Statuts documentés Stripe :
 * - succeeded / paid / complete                                     → SUCCES
 * - processing / requires_action / requires_confirmation            → EN_COURS
 * - requires_payment_method / canceled / failed                     → ECHEC
 *
 * Comportement sur statut inconnu : retourne INCONNU (jamais d'exception).
 */
final class StripeStatusMapper implements PaymentStatusMapper
{
    private const MAPPING = [
        'succeeded'                  => PaymentStatusEnum::SUCCES,
        'paid'                       => PaymentStatusEnum::SUCCES,
        'complete'                   => PaymentStatusEnum::SUCCES,
        'processing'                 => PaymentStatusEnum::EN_COURS,
        'requires_action'            => PaymentStatusEnum::EN_COURS,
        'requires_confirmation'      => PaymentStatusEnum::EN_COURS,
        'requires_payment_method'    => PaymentStatusEnum::ECHEC,
        'canceled'                   => PaymentStatusEnum::ECHEC,
        'failed'                     => PaymentStatusEnum::ECHEC,
    ];

    public function map(string $providerStatus): PaymentStatusEnum
    {
        return self::MAPPING[strtolower($providerStatus)] ?? PaymentStatusEnum::INCONNU;
    }

    public function isKnown(string $providerStatus): bool
    {
        return isset(self::MAPPING[strtolower($providerStatus)]);
    }
}
