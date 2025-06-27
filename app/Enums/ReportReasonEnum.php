<?php

namespace App\Enums;

enum ReportReasonEnum: int
{
    case FRAUDE = 0;
    case PERTE = 1;
    case RETOUR_TARDIF = 2;
    case DOMMAGE = 3;
    case AUTRE = 4;
}
