<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

interface KkiapayAdminClientContract
{
    public function verify(string $transactionId): array;
    public function refund(string $transactionId): array|bool;
}
