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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
