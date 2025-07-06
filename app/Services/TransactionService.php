<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function refund(Transaction $transaction): bool
    {
        if (! $transaction->canBeRefunded()) {
            return false;
        }

        return DB::transaction(function () use ($transaction) {
            try {
                // Exemple simple : statut + horodatage
                $transaction->status = 'refunded'; // ✅ idéalement utiliser un Enum
                $transaction->refunded_at = now();
                $transaction->save();

                Log::info("💰 Transaction remboursée", [
                    'transaction_id' => $transaction->id,
                    'user_id'        => $transaction->user_id,
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::error("❌ Échec du remboursement", [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        });
    }
}
