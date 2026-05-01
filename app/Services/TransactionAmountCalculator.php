<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionAmountCalculator
{
    public function calculateFeeAmount(Transaction $charge): float
    {
        return round($this->chargeAmount($charge) * $this->feeRate(), 2);
    }

    public function calculatePaymentFeeAmount(Transaction $charge): float
    {
        return round($this->chargeAmount($charge) * $this->paymentFeeRate(), 2);
    }

    public function calculatePayoutAmount(Transaction $charge): float
    {
        return round($this->chargeAmount($charge) - $this->calculateFeeAmount($charge), 2);
    }

    public function calculateRefundAmount(Transaction $charge): float
    {
        return round($this->chargeAmount($charge) - $this->calculateFeeAmount($charge), 2);
    }

    public function calculateNetProfitAmount(Transaction $charge): float
    {
        return round(
            $this->calculateFeeAmount($charge) - $this->calculatePaymentFeeAmount($charge),
            2
        );
    }

    private function chargeAmount(Transaction $charge): float
    {
        return (float) $charge->amount;
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
