<?php

namespace App\Enums;

enum InvitationStatusEnum: int
{
    case PENDING = 0;
    case USED = 1;
    case EXPIRED = 2;
}
