<?php

declare(strict_types=1);

namespace App\Services;

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
        if ($this->hasExistingEntries($transaction)) {
            return;
        }

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

        if ($this->hasExistingDebitOnAccount($chargeTransaction, "escrow_{$currency}")) {
            return;
        }

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
            $this->writeEntry(
                transaction: $chargeTransaction,
                slug: "escrow_{$currency}",
                direction: LedgerDirectionEnum::DEBIT,
                amount: $chargeAmount,
                description: "Payout release booking#{$chargeTransaction->booking_id} — escrow libéré",
            );

            $this->writeEntry(
                transaction: $payoutTransaction,
                slug: "payable_voyageur_{$currency}",
                direction: LedgerDirectionEnum::CREDIT,
                amount: $payoutAmount,
                description: "Payout release booking#{$chargeTransaction->booking_id} — dette voyageur",
            );

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
        if ($this->hasExistingEntries($payoutTransaction)) {
            return;
        }

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
        if ($this->hasExistingEntries($paymentFeeTransaction)) {
            return;
        }

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
        if ($this->hasExistingEntries($refundTransaction)) {
            return;
        }

        $currency = $this->currencySlug($chargeTransaction);

        $this->writeDoubleEntry(
            transaction: $refundTransaction,
            debitSlug: "escrow_{$currency}",
            creditSlug: "external_psp_clearing_{$currency}",
            amount: $refundTransaction->amount,
            description: "Refund booking#{$chargeTransaction->booking_id}",
        );
    }

    /**
     * F-017 — REFUND AFTER PAYOUT RELEASE — remboursement après libération escrow.
     *
     * Cas : dispute résolue en faveur sender après que le payout release a déjà eu lieu.
     * L'escrow est déjà vide — on reverse la dette voyageur et la fee.
     *
     * DEBIT  payable_voyageur_{currency}      +payout_amount
     * DEBIT  revenue_fees_{currency}          +fee_amount
     * CREDIT external_psp_clearing_{currency} +charge_amount
     *
     * Invariant : payout_amount + fee_amount = charge_amount (conservation de valeur)
     */
    public function writeRefundAfterPayoutRelease(
        Transaction $refundTransaction,
        Transaction $payoutTransaction,
        Transaction $feeTransaction,
    ): void {
        if ($this->hasExistingEntries($refundTransaction)) {
            return;
        }

        $currency     = $this->currencySlug($refundTransaction);
        $refundAmount = $refundTransaction->amount;
        $feeAmount    = $feeTransaction->amount;
        $totalCredit  = $refundAmount + $feeAmount;

        $this->assertBalanced($totalCredit, $totalCredit, 'REFUND_AFTER_PAYOUT_RELEASE');

        DB::transaction(function () use (
            $refundTransaction,
            $payoutTransaction,
            $feeTransaction,
            $currency,
            $refundAmount,
            $feeAmount,
            $totalCredit,
        ): void {
            // Reverse la dette voyageur
            $this->writeEntry(
                transaction: $refundTransaction,
                slug: "payable_voyageur_{$currency}",
                direction: LedgerDirectionEnum::DEBIT,
                amount: $refundAmount,
                description: "Refund post-release booking#{$refundTransaction->booking_id} — reverse voyageur",
            );

            // Reverse la commission
            $this->writeEntry(
                transaction: $feeTransaction,
                slug: "revenue_fees_{$currency}",
                direction: LedgerDirectionEnum::DEBIT,
                amount: $feeAmount,
                description: "Refund post-release booking#{$refundTransaction->booking_id} — reverse fee",
            );

            // Crédite PSP clearing (retour fonds vers sender)
            $this->writeEntry(
                transaction: $refundTransaction,
                slug: "external_psp_clearing_{$currency}",
                direction: LedgerDirectionEnum::CREDIT,
                amount: $totalCredit,
                description: "Refund post-release booking#{$refundTransaction->booking_id} — retour sender",
            );
        });
    }

    /**
     * F-017 — PAYOUT REVERSAL — annulation d'un payout PENDING (jamais exécuté PSP).
     *
     * Cas : payout release effectué mais le virement PSP n'a pas encore eu lieu
     * et doit être annulé (ex: dispute ouverte après release, avant payout paid).
     *
     * Reverse writePayoutRelease :
     * DEBIT  payable_voyageur_{currency}      +payout_amount
     * DEBIT  revenue_fees_{currency}          +fee_amount
     * CREDIT escrow_{currency}                +charge_amount
     *
     * Invariant : payout_amount + fee_amount = charge_amount
     */
    public function writePayoutReversal(
        Transaction $payoutTransaction,
        Transaction $feeTransaction,
        Transaction $chargeTransaction,
    ): void {
        $currency     = $this->currencySlug($chargeTransaction);
        $payoutAmount = $payoutTransaction->amount;
        $feeAmount    = $feeTransaction->amount;
        $chargeAmount = $chargeTransaction->amount;

        $this->assertBalanced($chargeAmount, $payoutAmount + $feeAmount, 'PAYOUT_REVERSAL');

        // Idempotence — vérifie si le reversal existe déjà sur payable_voyageur
        if ($this->hasReversalEntry($payoutTransaction, "payable_voyageur_{$currency}")) {
            return;
        }

        DB::transaction(function () use (
            $payoutTransaction,
            $feeTransaction,
            $chargeTransaction,
            $currency,
            $payoutAmount,
            $feeAmount,
            $chargeAmount,
        ): void {
            // Reverse la dette voyageur
            $this->writeEntry(
                transaction: $payoutTransaction,
                slug: "payable_voyageur_{$currency}",
                direction: LedgerDirectionEnum::DEBIT,
                amount: $payoutAmount,
                description: "Payout reversal booking#{$chargeTransaction->booking_id} — annulation dette voyageur",
            );

            // Reverse la commission
            $this->writeEntry(
                transaction: $feeTransaction,
                slug: "revenue_fees_{$currency}",
                direction: LedgerDirectionEnum::DEBIT,
                amount: $feeAmount,
                description: "Payout reversal booking#{$chargeTransaction->booking_id} — annulation fee",
            );

            // Recrédite l'escrow
            $this->writeEntry(
                transaction: $chargeTransaction,
                slug: "escrow_{$currency}",
                direction: LedgerDirectionEnum::CREDIT,
                amount: $chargeAmount,
                description: "Payout reversal booking#{$chargeTransaction->booking_id} — retour escrow",
            );
        });
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

    private function hasExistingEntries(Transaction $transaction): bool
    {
        return LedgerEntry::where('transaction_id', $transaction->id)->exists();
    }

    private function hasExistingDebitOnAccount(Transaction $transaction, string $slug): bool
    {
        $account = LedgerAccount::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return false;
        }

        return LedgerEntry::where('transaction_id', $transaction->id)
            ->where('account_id', $account->id)
            ->where('direction', LedgerDirectionEnum::DEBIT)
            ->exists();
    }

    /**
     * Vérifie si un reversal existe déjà sur un compte donné pour une transaction.
     * Utilisé pour l'idempotence de writePayoutReversal.
     */
    private function hasReversalEntry(Transaction $transaction, string $slug): bool
    {
        $account = LedgerAccount::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return false;
        }

        // Un reversal sur payable_voyageur est un DEBIT (inverse du CREDIT initial)
        return LedgerEntry::where('transaction_id', $transaction->id)
            ->where('account_id', $account->id)
            ->where('direction', LedgerDirectionEnum::DEBIT)
            ->exists();
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

    private function assertBalanced(int $debits, int $credits, string $context): void
    {
        if ($debits !== $credits) {
            throw new RuntimeException(
                "Ledger déséquilibré [{$context}] : debits={$debits} credits={$credits}"
            );
        }
    }
}
