<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case CARTE_BANCAIRE  = 'carte';
    case PAYPAL          = 'paypal';
    case CRYPTO          = 'crypto';
    case VIREMENT        = 'virement';
    case ESPECE          = 'espece';
    case MOBILE_MONEY    = 'mobile_money'; // Orange, Wave, etc.
    case CHEQUE          = 'cheque';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CARTE_BANCAIRE => 'Carte bancaire',
            self::PAYPAL         => 'PayPal',
            self::CRYPTO         => 'Cryptomonnaie',
            self::VIREMENT       => 'Virement bancaire',
            self::ESPECE         => 'Espèces',
            self::MOBILE_MONEY   => 'Mobile Money',
            self::CHEQUE         => 'Chèque',
        };
    }

    public function isDigital(): bool
    {
        return in_array($this, [
            self::CARTE_BANCAIRE,
            self::PAYPAL,
            self::CRYPTO,
            self::MOBILE_MONEY,
        ]);
    }

    //une vérification manuelle (ex: chèque, virement).
    public function requiresVerification(): bool
    {
        return in_array($this, [
            self::VIREMENT,
            self::CHEQUE,
        ]);
    }

    public function isInstant(): bool
    {
        return in_array($this, [
            self::CARTE_BANCAIRE,
            self::PAYPAL,
            self::MOBILE_MONEY,
        ]);
    }
}
