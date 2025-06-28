<?php

namespace App\Enums;


enum ReportReasonEnum: string
{
    case ABUS          = 'abusive_behaviour';
    case LOST_LUGGAGE  = 'luggage_not_delivered';
    case INAPPROPRIATE = 'inappropriate_communication';
    case SCAM_SUSPECT  = 'suspected_scam';

    /* Helpers facultatifs */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ABUS          => 'Comportement abusif',
            self::LOST_LUGGAGE  => 'Valise non livrée',
            self::INAPPROPRIATE => 'Communication inappropriée',
            self::SCAM_SUSPECT  => 'Escroquerie suspectée',
        };
    }
}
