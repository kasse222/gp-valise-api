<?php

namespace App\Enums;

enum PlanTypeEnum: string
{
    case FREE    = 'free';
    case BASIC   = 'basic';
    case PREMIUM = 'premium';
    case ENTREPRISE = 'entreprise';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }


    public function label(): string
    {
        return match ($this) {
            self::FREE       => 'Gratuit',
            self::BASIC      => 'Basique',
            self::PREMIUM    => 'Premium',
            self::ENTREPRISE => 'Entreprise',
        };
    }


    public function isPaid(): bool
    {
        return in_array($this, [
            self::BASIC,
            self::PREMIUM,
            self::ENTREPRISE,
        ], true);
    }


    public function isGiftable(): bool
    {
        return in_array($this, [
            self::PREMIUM,
            self::ENTREPRISE,
        ]);
    }


    public static function visible(): array
    {
        return [
            self::FREE,
            self::BASIC,
            self::PREMIUM,
        ];
    }
}
