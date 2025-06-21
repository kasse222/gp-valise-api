<?php

namespace App\Enums;

enum PlanTypeEnum: string
{
    case FREE    = 'free';
    case BASIC   = 'basic';
    case PREMIUM = 'premium';
    case ENTREPRISE = 'entreprise';

    /**
     * Pour validation dans les FormRequest
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Libellé lisible (affichage UI ou API)
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE       => 'Gratuit',
            self::BASIC      => 'Basique',
            self::PREMIUM    => 'Premium',
            self::ENTREPRISE => 'Entreprise',
        };
    }

    /**
     * Indique si le plan est payant
     */
    public function isPaid(): bool
    {
        return in_array($this, [
            self::BASIC,
            self::PREMIUM,
            self::ENTREPRISE,
        ], true);
    }

    /**
     * Peut-on offrir ce plan gratuitement (pour marketing) ?
     */
    public function isGiftable(): bool
    {
        return in_array($this, [
            self::PREMIUM,
            self::ENTREPRISE,
        ]);
    }

    /**
     * Plans visibles dans l'UI publique
     */
    public static function visible(): array
    {
        return [
            self::FREE,
            self::BASIC,
            self::PREMIUM,
        ];
    }
}
