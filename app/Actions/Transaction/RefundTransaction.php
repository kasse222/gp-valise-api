<?php

namespace App\Actions\Transaction;

use App\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RefundTransaction
{
    public function execute(Transaction $transaction, ?string $reason = null): Transaction
    {
        if (! $transaction->canBeRefunded()) {
            throw ValidationException::withMessages([
                'transaction' => 'Cette transaction ne peut pas être remboursée.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $transaction->update([
                'status' => TransactionStatusEnum::REFUNDED,
                'processed_at' => now(),
            ]);

            Log::info('💰 Transaction remboursée', [
                'transaction_id' => $transaction->id,
                'booking_id' => $transaction->booking_id,
                'user_id' => $transaction->user_id,
                'reason' => $reason,
            ]);

            return $transaction->fresh();
        });
    }
}
