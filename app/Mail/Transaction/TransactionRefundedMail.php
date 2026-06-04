<?php

declare(strict_types=1);

namespace App\Mail\Transaction;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionRefundedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre remboursement a été effectué',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.transaction.refunded',
            with: [
                'transaction'  => $this->transaction,
                'booking'      => $this->transaction->booking,
                'dashboardUrl' => config('app.frontend_url') . '/sender/bookings/' . $this->transaction->booking_id,
            ],
        );
    }
}
