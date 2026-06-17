<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;

/**
 * Mappe les statuts Kkiapay vers les statuts internes canoniques.
 *
 * Statuts documentés Kkiapay :
 * - transaction.success / success / successful / completed → SUCCES
 * - transaction.pending / pending / processing            → EN_COURS
 * - transaction.failed / failed / failure / cancelled     → ECHEC
 *
 * Comportement sur statut inconnu : retourne INCONNU (jamais d'exception).
 * Le caller doit logger et ignorer l'événement.
 */
final class KkiapayStatusMapper implements PaymentStatusMapper
{
    private const MAPPING = [
        'transaction.success' => PaymentStatusEnum::SUCCES,
        'success'             => PaymentStatusEnum::SUCCES,
        'successful'          => PaymentStatusEnum::SUCCES,
        'completed'           => PaymentStatusEnum::SUCCES,
        'transaction.pending' => PaymentStatusEnum::EN_COURS,
        'pending'             => PaymentStatusEnum::EN_COURS,
        'processing'          => PaymentStatusEnum::EN_COURS,
        'transaction.failed'  => PaymentStatusEnum::ECHEC,
        'failed'              => PaymentStatusEnum::ECHEC,
        'failure'             => PaymentStatusEnum::ECHEC,
        'cancelled'           => PaymentStatusEnum::ECHEC,
        'canceled'            => PaymentStatusEnum::ECHEC,
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
