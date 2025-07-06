<?php

namespace App\Enums;

enum CurrencyEnum: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case XOF = 'XOF';
    case GBP = 'GBP';
    case MAD = 'MAD';

    public function symbol(): string
    {
        return match ($this) {
            self::EUR => '€',
            self::USD => '$',
            self::XOF => 'CFA',
            self::GBP => '£',
            self::MAD => 'DH',
        };
    }
    public function label(): string
    {
        return match ($this) {
            self::EUR => 'Euro',
            self::USD => 'Dollar US',
            self::XOF => 'Franc CFA',
            self::GBP => 'Livre sterling',
            self::MAD => 'Dirham marocain',
        };
    }


    public static function default(): self
    {
        return self::EUR;       // tu peux changer ici si la devise par défaut évolue
    }
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
