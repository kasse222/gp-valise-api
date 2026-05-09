<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Enums\BookingStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Services\LedgerWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MarkPayoutCompleted
{
    public function __construct(
        private readonly LedgerWriter $ledger,
    ) {}

    public function execute(Transaction $payout): Transaction
    {
        return DB::transaction(function () use ($payout): Transaction {
            $payout = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($payout->id);

            $this->validate($payout);

            $payout->update([
                'status'       => TransactionStatusEnum::COMPLETED,
                'processed_at' => now(),
            ]);

            // ── Ledger : dette voyageur soldée ────────────────────────────────
            $this->ledger->writePayoutPaid($payout);
            // ─────────────────────────────────────────────────────────────────

            // Transition booking → TERMINE
            $booking = $payout->booking;
            if ($booking && $booking->status === BookingStatusEnum::LIVREE) {
                $booking->transitionTo(
                    BookingStatusEnum::TERMINE,
                    null,
                    'Payout complété — booking terminé'
                );
            }

            return $payout->fresh();
        });
    }

    private function validate(Transaction $payout): void
    {
        if ($payout->type !== TransactionTypeEnum::PAYOUT) {
            throw ValidationException::withMessages([
                'transaction' => 'Cette transaction n\'est pas un payout.',
            ]);
        }

        if ($payout->status === TransactionStatusEnum::COMPLETED) {
            throw ValidationException::withMessages([
                'transaction' => 'Ce payout est déjà complété.',
            ]);
        }

        if ($payout->status === TransactionStatusEnum::FAILED) {
            throw ValidationException::withMessages([
                'transaction' => 'Ce payout est en échec — impossible de le compléter.',
            ]);
        }

        if (! $payout->booking_id) {
            throw ValidationException::withMessages([
                'transaction' => 'Ce payout n\'est pas lié à un booking.',
            ]);
        }
    }
}
