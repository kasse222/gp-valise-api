<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Crée une transaction pour un utilisateur
     */
    public function create(User $user, array $data): Transaction
    {
        // 🔒 Exemple : on pourrait valider ici les montants minimum, le solde disponible, etc.
        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException("Le montant doit être positif.");
        }

        // 🧾 Création de la transaction
        $transaction = $user->transactions()->create($data);

        // 📚 Log ou event éventuel
        Log::info("Transaction créée", [
            'user_id' => $user->id,
            'type'    => $data['type'] ?? 'inconnu',
            'amount'  => $data['amount'],
        ]);

        return $transaction;
    }
}
