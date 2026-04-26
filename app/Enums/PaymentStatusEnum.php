<?php

namespace App\Enums;

enum PaymentStatusEnum: int
{
    case EN_ATTENTE     = 0;
    case EN_COURS       = 1;
    case SUCCES         = 2;
    case ECHEC          = 3;
    case REMBOURSE      = 4;
    case ANNULE         = 5;
    case FRAUDE         = 6;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_COURS   => 'En cours',
            self::SUCCES     => 'Succès',
            self::ECHEC      => 'Échec',
            self::REMBOURSE  => 'Remboursé',
            self::ANNULE     => 'Annulé',
            self::FRAUDE     => 'Fraude',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE  => 'gray',
            self::EN_COURS    => 'blue',
            self::SUCCES      => 'green',
            self::ECHEC       => 'red',
            self::REMBOURSE   => 'orange',
            self::ANNULE      => 'dark',
            self::FRAUDE      => 'black',
        };
    }


    public function isFinal(): bool
    {
        return in_array($this, [
            self::SUCCES,
            self::ECHEC,
            self::REMBOURSE,
            self::ANNULE,
            self::FRAUDE,
        ]);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::SUCCES;
    }

    public function canBeRetried(): bool
    {
        return in_array($this, [self::ECHEC, self::FRAUDE]);
    }

    public function isSuccess(): bool
    {
        return $this === self::SUCCES;
    }

    public function isFailed(): bool
    {
        return in_array($this, [self::ECHEC, self::FRAUDE]);
    }
}
