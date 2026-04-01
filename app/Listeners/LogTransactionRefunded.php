<?php

namespace App\Listeners;

use App\Events\TransactionRefunded;
use Illuminate\Support\Facades\Log;

class LogTransactionRefunded
{
    public function handle(TransactionRefunded $event): void
    {
        Log::info('transaction.refunded', [
            'transaction_id' => $event->transaction->id,
            'booking_id' => $event->transaction->booking_id,
            'user_id' => $event->transaction->user_id,
            'reason' => $event->reason,
            'status' => $event->transaction->status?->value,
            'processed_at' => $event->transaction->processed_at,
        ]);
    }
}
