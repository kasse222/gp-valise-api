<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Log;

class LogTransactionCreated
{
    public function handle(TransactionCreated $event): void
    {
        Log::info('transaction.created', [
            'transaction_id' => $event->transaction->id,
            'booking_id' => $event->transaction->booking_id,
            'user_id' => $event->transaction->user_id,
            'amount' => $event->transaction->amount,
            'status' => $event->transaction->status?->value,
        ]);
    }
}
