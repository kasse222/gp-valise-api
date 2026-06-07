<?php

declare(strict_types=1);

namespace App\Enums;

enum LuggageCategoryEnum: string
{
    case DOCUMENT   = 'document';
    case PHONE      = 'phone';
    case COMPUTER   = 'computer';
    case CLOTHES    = 'clothes';
    case COSMETICS  = 'cosmetics';
    case MEDICINE   = 'medicine';
    case OTHER      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DOCUMENT  => 'Document',
            self::PHONE     => 'Téléphone',
            self::COMPUTER  => 'Ordinateur',
            self::CLOTHES   => 'Vêtements',
            self::COSMETICS => 'Cosmétiques',
            self::MEDICINE  => 'Médicaments',
            self::OTHER     => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DOCUMENT  => '📄',
            self::PHONE     => '📱',
            self::COMPUTER  => '💻',
            self::CLOTHES   => '👕',
            self::COSMETICS => '💄',
            self::MEDICINE  => '💊',
            self::OTHER     => '📦',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
