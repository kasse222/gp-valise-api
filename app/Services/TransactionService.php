<?php

namespace App\Services;

use App\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    /**
     * ➕ Crée une transaction pour un utilisateur (dépôt ou paiement)
     */
    public function create(User $user, array $data): Transaction
    {
        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException("Le montant doit être positif.");
        }

        return DB::transaction(function () use ($user, $data) {
            $transaction = $user->transactions()->create($data);

            Log::info("✅ Transaction créée", [
                'user_id' => $user->id,
                'type'    => $data['type'] ?? 'inconnu',
                'amount'  => $data['amount'],
            ]);

            return $transaction;
        });
    }

    /**
     * 💸 Rembourse une transaction si elle est éligible
     */
    public function refund(Transaction $transaction, ?string $reason = null): Transaction
    {
        if (! $transaction->canBeRefunded()) {
            throw ValidationException::withMessages([
                'transaction' => 'Cette transaction ne peut pas être remboursée.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $transaction->update([
                'status'       => TransactionStatusEnum::REFUNDED,
                'refunded_at'  => now(),
                'refund_reason' => $reason,
            ]);

            Log::info("💰 Transaction remboursée", [
                'transaction_id' => $transaction->id,
                'user_id'        => $transaction->user_id,
            ]);

            return $transaction;
        });
    }
}
