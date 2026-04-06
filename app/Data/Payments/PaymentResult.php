<?php

namespace App\Data\Payments;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $providerTransactionId,
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly array $meta = [],
    ) {}
}
