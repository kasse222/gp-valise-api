<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;
use InvalidArgumentException;

final class KkiapayStatusMapper implements PaymentStatusMapper
{
    public function map(string $providerStatus): PaymentStatusEnum
    {
        return match (strtolower($providerStatus)) {
            'success', 'successful', 'completed' => PaymentStatusEnum::SUCCES,
            'pending', 'processing' => PaymentStatusEnum::EN_COURS,
            'failed', 'failure', 'cancelled', 'canceled' => PaymentStatusEnum::ECHEC,
            default => throw new InvalidArgumentException("Unknown Kkiapay payment status: {$providerStatus}"),
        };
    }
}
