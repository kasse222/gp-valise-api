<?php

namespace App\Enums;

enum InvitationStatusEnum: int
{
    case PENDING = 0;
    case USED    = 1;
    case EXPIRED = 2;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'En attente',
            self::USED => 'Acceptée',
            self::EXPIRED => 'Rejetée',
            default        => 'Inconnue',
        };
    }
    public function color(): string
    {
        return match ($this) {
            self::PENDING    => 'yellow',
            self::USED   => 'green',
            self::EXPIRED   => 'red',
            default          => 'gray',
        };
    }
}
