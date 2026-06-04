<?php

declare(strict_types=1);

namespace App\Mail\Dispute;

use App\Models\DisputeMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisputeMessageAddedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly DisputeMessage $disputeMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau message dans votre litige',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dispute.message-added',
            with: [
                'message'      => $this->disputeMessage,
                'dispute'      => $this->disputeMessage->dispute,
                'dashboardUrl' => config('app.frontend_url') . '/sender/bookings/' . $this->disputeMessage->dispute->booking_id,
            ],
        );
    }
}
