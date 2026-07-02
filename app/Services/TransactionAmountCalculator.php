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

    /**
     * F-033 — Remboursement d'annulation pondéré par le taux métier (100/70/0%).
     *
     * Base : chargeAmount - fee (= 90% du charge par défaut).
     *
     * Décision business E1 : si taux 100% doit signifier remboursement intégral
     * commission incluse, remplacer calculateRefundAmount($charge)
     * par chargeAmount($charge) ci-dessous.
     */
    public function calculateCancellationRefundAmount(Transaction $charge, int $refundRatePercent): int
    {
        $baseRefundable = $this->calculateRefundAmount($charge);

        return (int) round($baseRefundable * ($refundRatePercent / 100));
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
