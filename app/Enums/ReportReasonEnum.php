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
    public function color(): string
    {
        return match ($this) {
            self::ABUS          => 'red',
            self::LOST_LUGGAGE  => 'orange',
            self::INAPPROPRIATE => 'yellow',
            self::SCAM_SUSPECT  => 'red-dark',
        };
    }
    public function description(): string
    {
        return match ($this) {
            self::ABUS          => 'Insultes, menaces ou comportement agressif.',
            self::LOST_LUGGAGE  => 'Le bagage n’a pas été livré comme prévu.',
            self::INAPPROPRIATE => 'Messages à caractère déplacé ou offensant.',
            self::SCAM_SUSPECT  => 'Soupçon d’arnaque ou de demande frauduleuse.',
        };
    }
    public static function asSelect(): array
    {
        return collect(self::cases())->map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ])->toArray();
    }
}
