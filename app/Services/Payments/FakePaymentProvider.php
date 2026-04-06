<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentResult;
use Illuminate\Support\Str;

class FakePaymentProvider implements PaymentProvider
{
    public function charge(array $payload): PaymentResult
    {
        return new PaymentResult(
            success: true,
            providerTransactionId: 'fake_charge_' . Str::uuid(),
            status: 'completed',
            message: 'Fake charge accepted',
            meta: $payload,
        );
    }

    public function refund(array $payload): PaymentResult
    {
        return new PaymentResult(
            success: true,
            providerTransactionId: 'fake_refund_' . Str::uuid(),
            status: 'completed',
            message: 'Fake refund accepted',
            meta: $payload,
        );
    }

    public function payout(array $payload): PaymentResult
    {
        return new PaymentResult(
            success: true,
            providerTransactionId: 'fake_payout_' . Str::uuid(),
            status: 'pending',
            message: 'Fake payout created',
            meta: $payload,
        );
    }
}
