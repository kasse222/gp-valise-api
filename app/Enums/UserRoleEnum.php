<?php

namespace App\Enums;

enum UserRoleEnum: int
{
    case ADMIN     = 1;
    case TRAVELER  = 2;
    case SENDER    = 3;
    case MODERATOR = 4;
    case SUPPORT   = 5;
    case SUPER_ADMIN = 6;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN     => 'Administrateur',
            self::TRAVELER  => 'Voyageur',
            self::SENDER    => 'Expéditeur',
            self::MODERATOR => 'Modérateur',
            self::SUPPORT   => 'Support',
            self::SUPER_ADMIN => 'is_super_admin'
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::ADMIN, self::MODERATOR, self::SUPPORT]);
    }
}
