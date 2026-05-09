<?php

declare(strict_types=1);

namespace App\Enums;

enum LedgerAccountTypeEnum: string
{
    case ASSET     = 'ASSET';
    case LIABILITY = 'LIABILITY';
    case REVENUE   = 'REVENUE';
    case EXPENSE   = 'EXPENSE';
}
