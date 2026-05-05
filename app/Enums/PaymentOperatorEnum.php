<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentOperatorEnum: string
{
    case WAVE = 'wave';
    case ORANGE_MONEY = 'orange_money';
    case MTN = 'mtn';
    case MOOV = 'moov';
    case FREE_MONEY = 'free_money';

    /**
     * Liste brute des valeurs (validation FormRequest)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Libellé lisible (UI / logs)
     */
    public function label(): string
    {
        return match ($this) {
            self::WAVE => 'Wave',
            self::ORANGE_MONEY => 'Orange Money',
            self::MTN => 'MTN Mobile Money',
            self::MOOV => 'Moov Money',
            self::FREE_MONEY => 'Free Money',
        };
    }

    /**
     * Tous les opérateurs actuels sont du mobile money
     */
    public function isMobileMoney(): bool
    {
        return true;
    }

    /**
     * Vérifie si l’opérateur est supporté dans un pays donné
     * (utile pour validation métier / PSP routing)
     */
    public function supportsCountry(string $country): bool
    {
        return match ($this) {
            self::WAVE,
            self::ORANGE_MONEY,
            self::MTN,
            self::MOOV,
            self::FREE_MONEY => in_array($country, ['SN', 'CI', 'TG', 'BJ'], true),
        };
    }
}
