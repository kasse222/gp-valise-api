<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CurrencyEnum;
use App\Enums\LedgerDirectionEnum;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LedgerWriter
{
    /**
     * CHARGE — fonds reçus, bloqués en escrow.
     *
     * DEBIT  external_psp_clearing_{currency}  +amount
     * CREDIT escrow_{currency}                 +amount
     */
    public function writeCharge(Transaction $transaction): void
    {
        $currency = $this->currencySlug($transaction);

        $this->writeDoubleEntry(
            transaction: $transaction,
            debitSlug: "external_psp_clearing_{$currency}",
            creditSlug: "escrow_{$currency}",
            amount: $transaction->amount,
            description: "Charge booking#{$transaction->booking_id}",
        );
    }

    /**
     * PAYOUT RELEASE — escrow libéré, dette voyageur + revenue reconnus.
     *
     * DEBIT  escrow_{currency}              +charge_amount
     * CREDIT payable_voyageur_{currency}    +payout_amount
     * CREDIT revenue_fees_{currency}        +fee_amount
     */
    public function writePayoutRelease(
        Transaction $chargeTransaction,
        Transaction $payoutTransaction,
        Transaction $feeTransaction,
    ): void {
        $currency = $this->currencySlug($chargeTransaction);
        $chargeAmount = $chargeTransaction->amount;
        $payoutAmount = $payoutTransaction->amount;
        $feeAmount    = $feeTransaction->amount;

        $this->assertBalanced($chargeAmount, $payoutAmount + $feeAmount, 'PAYOUT_RELEASE');

        DB::transaction(function () use (
            $chargeTransaction,
            $payoutTransaction,
            $feeTransaction,
            $currency,
            $chargeAmount,
            $payoutAmount,
            $feeAmount,
        ): void {
            // DEBIT escrow
            $this->writeEntry(
                transaction: $chargeTransaction,
                slug: "escrow_{$currency}",
                direction: LedgerDirectionEnum::DEBIT,
                amount: $chargeAmount,
                description: "Payout release booking#{$chargeTransaction->booking_id} — escrow libéré",
            );

            // CREDIT payable_voyageur
            $this->writeEntry(
                transaction: $payoutTransaction,
                slug: "payable_voyageur_{$currency}",
                direction: LedgerDirectionEnum::CREDIT,
                amount: $payoutAmount,
                description: "Payout release booking#{$chargeTransaction->booking_id} — dette voyageur",
            );

            // CREDIT revenue_fees
            $this->writeEntry(
                transaction: $feeTransaction,
                slug: "revenue_fees_{$currency}",
                direction: LedgerDirectionEnum::CREDIT,
                amount: $feeAmount,
                description: "Payout release booking#{$chargeTransaction->booking_id} — commission",
            );
        });
    }

    /**
     * PAYOUT PAID — dette voyageur soldée.
     *
     * DEBIT  payable_voyageur_{currency}      +amount
     * CREDIT external_psp_clearing_{currency} +amount
     */
    public function writePayoutPaid(Transaction $payoutTransaction): void
    {
        $currency = $this->currencySlug($payoutTransaction);

        $this->writeDoubleEntry(
            transaction: $payoutTransaction,
            debitSlug: "payable_voyageur_{$currency}",
            creditSlug: "external_psp_clearing_{$currency}",
            amount: $payoutTransaction->amount,
            description: "Payout paid booking#{$payoutTransaction->booking_id}",
        );
    }

    /**
     * PAYMENT_FEE — coût PSP comptabilisé.
     *
     * DEBIT  expense_psp_{currency}           +amount
     * CREDIT external_psp_clearing_{currency} +amount
     */
    public function writePaymentFee(Transaction $paymentFeeTransaction): void
    {
        $currency = $this->currencySlug($paymentFeeTransaction);

        $this->writeDoubleEntry(
            transaction: $paymentFeeTransaction,
            debitSlug: "expense_psp_{$currency}",
            creditSlug: "external_psp_clearing_{$currency}",
            amount: $paymentFeeTransaction->amount,
            description: "Payment fee booking#{$paymentFeeTransaction->booking_id}",
        );
    }

    /**
     * REFUND BEFORE PAYOUT RELEASE — remboursement avant escrow libéré.
     *
     * DEBIT  escrow_{currency}                +amount
     * CREDIT external_psp_clearing_{currency} +amount
     */
    public function writeRefund(Transaction $chargeTransaction, Transaction $refundTransaction): void
    {
        $currency = $this->currencySlug($chargeTransaction);

        $this->writeDoubleEntry(
            transaction: $refundTransaction,
            debitSlug: "escrow_{$currency}",
            creditSlug: "external_psp_clearing_{$currency}",
            amount: $refundTransaction->amount,
            description: "Refund booking#{$chargeTransaction->booking_id}",
        );
    }

    // ── private ───────────────────────────────────────────────────────────────

    private function writeDoubleEntry(
        Transaction $transaction,
        string $debitSlug,
        string $creditSlug,
        int $amount,
        string $description,
    ): void {
        DB::transaction(function () use (
            $transaction,
            $debitSlug,
            $creditSlug,
            $amount,
            $description,
        ): void {
            $this->writeEntry($transaction, $debitSlug,  LedgerDirectionEnum::DEBIT,  $amount, $description);
            $this->writeEntry($transaction, $creditSlug, LedgerDirectionEnum::CREDIT, $amount, $description);
        });
    }

    private function writeEntry(
        Transaction $transaction,
        string $slug,
        LedgerDirectionEnum $direction,
        int $amount,
        string $description,
    ): void {
        $account = LedgerAccount::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw new RuntimeException("LedgerAccount introuvable : {$slug}");
        }

        LedgerEntry::create([
            'account_id'     => $account->id,
            'transaction_id' => $transaction->id,
            'direction'      => $direction,
            'amount'         => $amount,
            'currency'       => strtoupper($this->currencySlug($transaction)),
            'description'    => $description,
        ]);
    }

    private function assertBalanced(int $debits, int $credits, string $context): void
    {
        if ($debits !== $credits) {
            throw new RuntimeException(
                "Ledger déséquilibré [{$context}] : debits={$debits} credits={$credits}"
            );
        }
    }
    private function currencySlug(Transaction $transaction): string
    {
        $currency = $transaction->currency;

        return strtolower(
            $currency instanceof \App\Enums\CurrencyEnum
                ? $currency->value
                : (string) $currency
        );
    }
}
