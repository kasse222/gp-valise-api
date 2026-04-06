<?php

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentResult;

interface PaymentProvider
{
    public function charge(array $payload): PaymentResult;

    public function refund(array $payload): PaymentResult;

    public function payout(array $payload): PaymentResult;
}
