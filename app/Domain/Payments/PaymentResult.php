<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use App\Enums\PaymentProviderEnum;
use App\Enums\TransactionStatusEnum;

final class PaymentResult
{
    public function __construct(
        public readonly PaymentProviderEnum $provider,
        public readonly string $providerTransactionId,
        public readonly TransactionStatusEnum $status,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $eventId = null,
        public readonly array $rawPayload = [],
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatusEnum::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatusEnum::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatusEnum::FAILED;
    }
}
