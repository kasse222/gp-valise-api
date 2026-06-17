<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;

/**
 * Mappe les statuts PayDunya vers les statuts internes canoniques.
 *
 * PayDunya retourne le statut dans le champ `status` du webhook IPN.
 *
 * Statuts documentés PayDunya :
 * - completed                    → SUCCES
 * - cancelled / failed / expired → ECHEC
 * - pending / initiated          → EN_COURS
 *
 * Note : PayDunya sandbox peut retourner des statuts non documentés.
 * Comportement sur statut inconnu : retourne INCONNU (jamais d'exception).
 * Capturer les payloads réels en production pour enrichir ce mapping.
 */
final class PayDunyaStatusMapper implements PaymentStatusMapper
{
    private const MAPPING = [
        'completed'  => PaymentStatusEnum::SUCCES,
        'cancelled'  => PaymentStatusEnum::ECHEC,
        'canceled'   => PaymentStatusEnum::ECHEC,
        'failed'     => PaymentStatusEnum::ECHEC,
        'expired'    => PaymentStatusEnum::ECHEC,
        'rejected'   => PaymentStatusEnum::ECHEC,
        'pending'    => PaymentStatusEnum::EN_COURS,
        'initiated'  => PaymentStatusEnum::EN_COURS,
        'processing' => PaymentStatusEnum::EN_COURS,
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
