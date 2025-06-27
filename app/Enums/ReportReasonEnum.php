<?php

namespace App\Enums;

enum ReportReasonEnum: string
{
    case OBJET_ENDOMMAGE = 'objet_endommage';
    case RETARD_LIVRAISON = 'retard_livraison';
    case COMPORTEMENT_INAPPROPRIE = 'comportement_inapproprie';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::OBJET_ENDOMMAGE        => 'Objet endommagé',
            self::RETARD_LIVRAISON       => 'Retard de livraison',
            self::COMPORTEMENT_INAPPROPRIE => 'Comportement inapproprié',
            self::AUTRE                  => 'Autre',
        };
    }
}
