<?php

namespace App\Actions\Transaction;

use App\Enums\TransactionStatusEnum;
use App\Events\TransactionRefunded;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundTransaction
{
    public function execute(Transaction $transaction, ?string $reason = null): Transaction
    {
        $transaction = DB::transaction(function () use ($transaction) {
            $now = now();

            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if (! $transaction->canBeRefunded()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Cette transaction ne peut pas être remboursée.',
                ]);
            }

            $transaction->update([
                'status' => TransactionStatusEnum::REFUNDED,
                'processed_at' => $now,
            ]);

            return $transaction->fresh();
        });

        event(new TransactionRefunded($transaction, $reason));

        return $transaction;
    }
}
