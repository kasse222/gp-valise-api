<?php

declare(strict_types=1);

namespace App\Enums;

enum LedgerDirectionEnum: string
{
    case DEBIT  = 'DEBIT';
    case CREDIT = 'CREDIT';
}
