<?php

namespace App\Enums;

enum CurrencyEnum: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case XOF = 'XOF';
    case GBP = 'GBP';
    case MAD = 'MAD';

    /**
     * Mapping canonique country → currency.
     *
     * Utilisé par CreateTransaction pour résoudre la devise
     * depuis le pays quand currency n'est pas fourni explicitement.
     *
     * Règle : jamais de float, jamais de calcul — le montant
     * est toujours persisté en minor units par l'appelant.
     * XOF : 1 FCFA = 1 unité (pas de sous-unité)
     * EUR/MAD/GBP : 1 unité = 100 centimes
     */
    private const COUNTRY_MAP = [
        'SN' => self::XOF,  // Sénégal
        'CI' => self::XOF,  // Côte d'Ivoire
        'BJ' => self::XOF,  // Bénin
        'TG' => self::XOF,  // Togo
        'ML' => self::XOF,  // Mali
        'BF' => self::XOF,  // Burkina Faso
        'GW' => self::XOF,  // Guinée-Bissau
        'NE' => self::XOF,  // Niger
        'FR' => self::EUR,  // France
        'BE' => self::EUR,  // Belgique
        'DE' => self::EUR,  // Allemagne
        'ES' => self::EUR,  // Espagne
        'IT' => self::EUR,  // Italie
        'PT' => self::EUR,  // Portugal
        'MA' => self::MAD,  // Maroc
        'GB' => self::GBP,  // Royaume-Uni
        'US' => self::USD,  // États-Unis
    ];

    /**
     * Résout la devise canonique depuis un code pays ISO 3166-1 alpha-2.
     * Retourne EUR par défaut si le pays n'est pas mappé.
     */
    public static function forCountry(string $country): self
    {
        return self::COUNTRY_MAP[strtoupper(trim($country))] ?? self::EUR;
    }

    /**
     * Indique si la devise utilise des sous-unités (centimes).
     * XOF n'a pas de sous-unité — 1 FCFA = 1 unité.
     */
    public function hasSubunit(): bool
    {
        return match ($this) {
            self::XOF => false,
            default   => true,
        };
    }

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
        return self::EUR;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
