<?php

declare(strict_types=1);

namespace App\Payments\Mappers;

use App\Enums\PaymentStatusEnum;
use InvalidArgumentException;

final class StripeStatusMapper implements PaymentStatusMapper
{
    public function map(string $providerStatus): PaymentStatusEnum
    {
        return match (strtolower($providerStatus)) {
            'succeeded', 'paid', 'complete' => PaymentStatusEnum::SUCCES,
            'processing', 'requires_action', 'requires_confirmation' => PaymentStatusEnum::EN_COURS,
            'requires_payment_method', 'canceled', 'failed' => PaymentStatusEnum::ECHEC,
            default => throw new InvalidArgumentException("Unknown Stripe payment status: {$providerStatus}"),
        };
    }
}
