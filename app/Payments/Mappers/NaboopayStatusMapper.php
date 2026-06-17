<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;

/**
 * Mappe les statuts Naboopay vers les statuts internes canoniques.
 *
 * Naboopay agrège Wave, Orange Money, Free Money (West Africa).
 * Les statuts peuvent varier selon l'opérateur sous-jacent.
 *
 * Statuts documentés Naboopay :
 * - success / successful / completed / paid   → SUCCES
 * - pending / processing / initiated          → EN_COURS
 * - failed / failure / cancelled / expired    → ECHEC
 *
 * Note : capturer les payloads réels en production — la doc Naboopay
 * peut avoir des écarts avec les statuts réellement envoyés.
 * Comportement sur statut inconnu : retourne INCONNU (jamais d'exception).
 */
final class NaboopayStatusMapper implements PaymentStatusMapper
{
    private const MAPPING = [
        'success'    => PaymentStatusEnum::SUCCES,
        'successful' => PaymentStatusEnum::SUCCES,
        'completed'  => PaymentStatusEnum::SUCCES,
        'paid'       => PaymentStatusEnum::SUCCES,
        'pending'    => PaymentStatusEnum::EN_COURS,
        'processing' => PaymentStatusEnum::EN_COURS,
        'initiated'  => PaymentStatusEnum::EN_COURS,
        'failed'     => PaymentStatusEnum::ECHEC,
        'failure'    => PaymentStatusEnum::ECHEC,
        'cancelled'  => PaymentStatusEnum::ECHEC,
        'canceled'   => PaymentStatusEnum::ECHEC,
        'expired'    => PaymentStatusEnum::ECHEC,
        'rejected'   => PaymentStatusEnum::ECHEC,
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
