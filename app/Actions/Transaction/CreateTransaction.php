<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateTransaction
{
    public function execute(User $user, array $data): Transaction
    {
        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($user, $data) {
            $transaction = $user->transactions()->create($data);

            Log::info('✅ Transaction créée', [
                'user_id' => $user->id,
                'type' => $data['type'] ?? 'inconnu',
                'amount' => $data['amount'],
            ]);

            return $transaction;
        });
    }
}
