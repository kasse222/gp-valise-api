<?php

namespace App\Enums;

enum LocationTypeEnum: int
{
    case DEPARTURE = 0;
    case STOP = 1;
    case ARRIVAL = 2;
}
