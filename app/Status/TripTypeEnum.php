<?php

namespace App\Status;

enum TripTypeEnum: string
{
    case STANDARD = 'standard';
    case EXPRESS = 'express';
    case SUR_DEVIS = 'sur_devis';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
