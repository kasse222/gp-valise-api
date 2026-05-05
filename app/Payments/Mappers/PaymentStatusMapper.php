<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;

interface PaymentStatusMapper
{
    public function map(string $providerStatus): PaymentStatusEnum;
}
