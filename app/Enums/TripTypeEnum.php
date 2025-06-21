<?php

namespace App\Enums;

enum TripTypeEnum: string
{
    case STANDARD  = 'standard';
    case EXPRESS   = 'express';
    case SUR_DEVIS = 'sur_devis';

    /**
     * Retourne la liste des valeurs utilisables en base
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Libellé lisible pour affichage UI
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD  => 'Standard',
            self::EXPRESS   => 'Express',
            self::SUR_DEVIS => 'Sur devis',
        };
    }

    /**
     * Ce trajet est-il un express ?
     */
    public function isExpress(): bool
    {
        return $this === self::EXPRESS;
    }
}
