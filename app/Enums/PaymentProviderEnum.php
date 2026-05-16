<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentProviderEnum: string
{
    case FAKE = 'fake';
    case KKIAPAY = 'kkiapay';
    case STRIPE = 'stripe';
    case PAYDUNYA = 'paydunya';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::FAKE => 'Fake Provider',
            self::KKIAPAY => 'Kkiapay',
            self::STRIPE => 'Stripe',
            self::PAYDUNYA => 'paydunya'
        };
    }

    public function isSandbox(): bool
    {
        return $this === self::FAKE;
    }

    public function supportsCountry(string $country): bool
    {
        $country = strtoupper($country);

        return match ($this) {
            self::KKIAPAY => in_array($country, ['SN', 'CI', 'TG', 'BJ'], true),
            self::PAYDUNYA => in_array($country, ['SN', 'CI', 'BJ', 'TG'], true),
            self::STRIPE => in_array($country, ['FR', 'BE', 'DE', 'ES', 'MA'], true),
            self::FAKE => true,
        };
    }
}
