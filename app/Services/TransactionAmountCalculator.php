<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;

class TransactionAmountCalculator
{
    public function calculateFeeAmount(Transaction $charge): int
    {
        return (int) round($this->chargeAmount($charge) * $this->feeRate());
    }

    public function calculatePaymentFeeAmount(Transaction $charge): int
    {
        return (int) round($this->chargeAmount($charge) * $this->paymentFeeRate());
    }

    public function calculatePayoutAmount(Transaction $charge): int
    {
        return $this->chargeAmount($charge) - $this->calculateFeeAmount($charge);
    }

    public function calculateRefundAmount(Transaction $charge): int
    {
        return $this->chargeAmount($charge) - $this->calculateFeeAmount($charge);
    }

    public function calculateNetProfitAmount(Transaction $charge): int
    {
        return $this->calculateFeeAmount($charge) - $this->calculatePaymentFeeAmount($charge);
    }

    private function chargeAmount(Transaction $charge): int
    {
        return (int) $charge->amount;
    }

    private function feeRate(): float
    {
        return ((float) config('gpvalise.fee_percentage', 10)) / 100;
    }

    private function paymentFeeRate(): float
    {
        return ((float) config('gpvalise.payment_fee_percentage', 2)) / 100;
    }
}
