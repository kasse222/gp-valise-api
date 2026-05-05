<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case CARD = 'card';
    case MOBILE_MONEY = 'mobile_money';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Carte bancaire',
            self::MOBILE_MONEY => 'Mobile Money',
            self::BANK_TRANSFER => 'Virement bancaire',
            self::CASH => 'Espèces',
        };
    }

    public function isInstant(): bool
    {
        return match ($this) {
            self::CARD,
            self::MOBILE_MONEY => true,
            default => false,
        };
    }

    public function requiresVerification(): bool
    {
        return match ($this) {
            self::BANK_TRANSFER => true,
            default => false,
        };
    }
}
