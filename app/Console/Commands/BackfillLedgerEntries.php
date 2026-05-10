<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Services\LedgerWriter;
use Illuminate\Console\Command;

final class BackfillLedgerEntries extends Command
{
    protected $signature   = 'ledger:backfill {--dry-run : Affiche sans écrire}';
    protected $description = 'Rétro-alimente les ledger_entries depuis les transactions existantes';

    public function handle(LedgerWriter $writer): int
    {
        $dryRun = $this->option('dry-run');

        // ── CHARGES ───────────────────────────────────────────────────────────
        $charges = Transaction::query()
            ->where('type', TransactionTypeEnum::CHARGE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->get();

        $this->info("CHARGE : {$charges->count()} transactions");

        foreach ($charges as $charge) {
            if ($dryRun) {
                $this->line("  [dry] writeCharge #{$charge->id} {$charge->amount} {$charge->currency->value}");
                continue;
            }
            try {
                $writer->writeCharge($charge);
                $this->line("  ✓ writeCharge #{$charge->id}");
            } catch (\Throwable $e) {
                $this->warn("  ✗ #{$charge->id} : {$e->getMessage()}");
            }
        }

        // ── REFUNDS ───────────────────────────────────────────────────────────
        $refunds = Transaction::query()
            ->where('type', TransactionTypeEnum::REFUND)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->with('booking.transactions')
            ->get();

        $this->info("REFUND : {$refunds->count()} transactions");

        foreach ($refunds as $refund) {
            $charge = $refund->booking?->transactions()
                ->where('type', TransactionTypeEnum::CHARGE)
                ->where('status', TransactionStatusEnum::COMPLETED)
                ->latest()
                ->first();

            if (! $charge) {
                $this->warn("  ✗ REFUND #{$refund->id} : pas de CHARGE associée");
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry] writeRefund #{$refund->id} booking#{$refund->booking_id}");
                continue;
            }

            try {
                $writer->writeRefund($charge, $refund);
                $this->line("  ✓ writeRefund #{$refund->id}");
            } catch (\Throwable $e) {
                $this->warn("  ✗ #{$refund->id} : {$e->getMessage()}");
            }
        }

        // ── PAYOUTS ───────────────────────────────────────────────────────────
        $payouts = Transaction::query()
            ->where('type', TransactionTypeEnum::PAYOUT)
            ->whereIn('status', [TransactionStatusEnum::PENDING, TransactionStatusEnum::COMPLETED])
            ->with('booking.transactions')
            ->get();

        $this->info("PAYOUT : {$payouts->count()} transactions");

        foreach ($payouts as $payout) {
            $charge = $payout->booking?->transactions()
                ->where('type', TransactionTypeEnum::CHARGE)
                ->where('status', TransactionStatusEnum::COMPLETED)
                ->latest()
                ->first();

            $fee = $payout->booking?->transactions()
                ->where('type', TransactionTypeEnum::FEE)
                ->latest()
                ->first();

            if (! $charge || ! $fee) {
                $this->warn("  ✗ PAYOUT #{$payout->id} : CHARGE ou FEE manquante");
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry] writePayoutRelease #{$payout->id} booking#{$payout->booking_id}");
                if ($payout->status === TransactionStatusEnum::COMPLETED) {
                    $this->line("  [dry] writePayoutPaid #{$payout->id}");
                }
                continue;
            }

            try {
                $writer->writePayoutRelease($charge, $payout, $fee);
                $this->line("  ✓ writePayoutRelease #{$payout->id}");
            } catch (\Throwable $e) {
                $this->warn("  ✗ writePayoutRelease #{$payout->id} : {$e->getMessage()}");
            }

            if ($payout->status === TransactionStatusEnum::COMPLETED) {
                try {
                    $writer->writePayoutPaid($payout);
                    $this->line("  ✓ writePayoutPaid #{$payout->id}");
                } catch (\Throwable $e) {
                    $this->warn("  ✗ writePayoutPaid #{$payout->id} : {$e->getMessage()}");
                }
            }
        }

        // ── PAYMENT_FEES ──────────────────────────────────────────────────────
        $paymentFees = Transaction::query()
            ->where('type', TransactionTypeEnum::PAYMENT_FEE)
            ->where('status', TransactionStatusEnum::COMPLETED)
            ->get();

        $this->info("PAYMENT_FEE : {$paymentFees->count()} transactions");

        foreach ($paymentFees as $paymentFee) {
            if ($dryRun) {
                $this->line("  [dry] writePaymentFee #{$paymentFee->id} {$paymentFee->amount} {$paymentFee->currency->value}");
                continue;
            }

            try {
                $writer->writePaymentFee($paymentFee);
                $this->line("  ✓ writePaymentFee #{$paymentFee->id}");
            } catch (\Throwable $e) {
                $this->warn("  ✗ #{$paymentFee->id} : {$e->getMessage()}");
            }
        }

        $this->info('✔ Backfill terminé.');

        return Command::SUCCESS;
    }
}
