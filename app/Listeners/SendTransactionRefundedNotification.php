<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionRefunded;
use App\Mail\Transaction\TransactionRefundedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTransactionRefundedNotification implements ShouldQueue
{
    public function handle(TransactionRefunded $event): void
    {
        $transaction = $event->transaction->loadMissing(['booking.user']);

        Mail::to($transaction->booking->user->email)
            ->queue(new TransactionRefundedMail($transaction));
    }
}
