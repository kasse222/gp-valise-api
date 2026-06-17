<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;

interface PaymentStatusMapper
{
    /**
     * Mappe un statut PSP brut vers un statut interne canonique.
     *
     * Ne lève jamais d'exception — retourne INCONNU si le statut n'est pas reconnu.
     * Le caller est responsable de logger et d'ignorer les événements inconnus.
     */
    public function map(string $providerStatus): PaymentStatusEnum;

    /**
     * Indique si le statut est reconnu par ce mapper.
     */
    public function isKnown(string $providerStatus): bool;
}
