<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProvider;
use App\Data\Payments\PaymentResult;
use Illuminate\Support\Str;

class FakePaymentProvider implements PaymentProvider
{
    public function charge(array $payload): PaymentResult
    {
        return $this->simulate('charge', $payload);
    }

    public function refund(array $payload): PaymentResult
    {
        return $this->simulate('refund', $payload);
    }

    public function payout(array $payload): PaymentResult
    {
        return $this->simulate('payout', $payload);
    }

    private function simulate(string $type, array $payload): PaymentResult
    {
        $mode = $payload['force_status'] ?? config('payment.fake.mode', 'success');

        return match ($mode) {
            'failure' => new PaymentResult(
                success: false,
                providerTransactionId: 'fake_' . $type . '_' . Str::uuid(),
                status: 'failed',
                message: 'Simulated failure',
                meta: $payload,
            ),

            'pending' => new PaymentResult(
                success: true,
                providerTransactionId: 'fake_' . $type . '_' . Str::uuid(),
                status: 'pending',
                message: 'Simulated pending payment',
                meta: $payload,
            ),

            default => new PaymentResult(
                success: true,
                providerTransactionId: 'fake_' . $type . '_' . Str::uuid(),
                status: 'completed',
                message: 'Simulated success',
                meta: $payload,
            ),
        };
    }
}
