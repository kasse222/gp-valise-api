<?php

namespace App\Enums;

enum LocationTypeEnum: string
{
    case VILLE     = 'ville';
    case DOUANE    = 'douane';
    case HUB       = 'hub';
    case ETAPE     = 'etape'; // étape classique sans catégorie
    case AEROPORT  = 'aeroport'; // pour départ ou arrivée



    public function label(): string
    {
        return match ($this) {
            self::VILLE     => 'Ville',
            self::DOUANE    => 'Contrôle douanier',
            self::HUB       => 'Centre logistique',
            self::ETAPE     => 'Étape intermédiaire',
            self::AEROPORT  => 'Aéroport',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VILLE     => '🏙️',
            self::DOUANE    => '🛃',
            self::HUB       => '📦',
            self::ETAPE     => '📍',
            self::AEROPORT  => '✈️',
        };
    }
}
