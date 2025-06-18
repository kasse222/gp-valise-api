<?php

namespace App\Status;

enum LuggageStatus: string
{
    case EN_ATTENTE = 'en_attente';     // dispo à réserver
    case RESERVEE   = 'reservee';       // liée à une réservation
    case LIVREE     = 'livree';         // remise à destination
    case ANNULEE    = 'annulee';        // annulée
    case PERDUE     = 'perdue';         // litige ou perte


    public function isReservable(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    public function isModifiable(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::RESERVEE]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::LIVREE, self::PERDUE]);
    }
}
