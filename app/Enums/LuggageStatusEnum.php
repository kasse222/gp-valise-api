<?php

namespace App\Enums;

enum LuggageStatusEnum: string
{

    case EN_ATTENTE  = 'en_attente';
    case RESERVEE    = 'reservee';
    case EN_TRANSIT  = 'en_transit';
    case LIVREE      = 'livree';


    case ANNULEE     = 'annulee';
    case PERDUE      = 'perdue';
    case RETOUR      = 'retour';



    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::RESERVEE   => 'Réservée',
            self::EN_TRANSIT => 'En transit',
            self::LIVREE     => 'Livrée',
            self::ANNULEE    => 'Annulée',
            self::PERDUE     => 'Perdue',
            self::RETOUR     => 'Retour',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'gray',
            self::RESERVEE   => 'blue',
            self::EN_TRANSIT => 'indigo',
            self::LIVREE     => 'green',
            self::ANNULEE    => 'red',
            self::PERDUE     => 'orange',
            self::RETOUR     => 'purple',
        };
    }



    public function isReservable(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    public function isTransportable(): bool
    {
        return in_array($this, [
            self::RESERVEE,
            self::EN_TRANSIT,
        ], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::LIVREE,
            self::ANNULEE,
            self::PERDUE,
            self::RETOUR,
        ], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::EN_ATTENTE,
            self::RESERVEE,
        ], true);
    }

    public function canBeDelivered(): bool
    {
        return $this === self::EN_TRANSIT;
    }

    public function canBeDisputed(): bool
    {
        return $this === self::LIVREE;
    }


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return array_combine(
            self::values(),
            array_map(fn(self $case) => $case->label(), self::cases())
        );
    }
}
