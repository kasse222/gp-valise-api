<?php

namespace App\Enums;

enum LocationPositionEnum: string
{
    case DEPART    = 'depart';
    case ETAPE     = 'etape';
    case ARRIVEE   = 'arrivee';

    public function label(): string
    {
        return match ($this) {
            self::DEPART  => 'Point de départ',
            self::ETAPE   => 'Étape intermédiaire',
            self::ARRIVEE => 'Point d’arrivée',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DEPART  => '🛫',
            self::ETAPE   => '📍',
            self::ARRIVEE => '🛬',
        };
    }
}
