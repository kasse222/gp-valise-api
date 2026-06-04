<?php

declare(strict_types=1);

namespace App\Mail\Dispute;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisputeStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Dispute $dispute
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mise à jour du statut de votre litige',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.dispute.status-changed',
            with: [
                'dispute'      => $this->dispute,
                'booking'      => $this->dispute->booking,
                'dashboardUrl' => config('app.frontend_url') . '/sender/bookings/' . $this->dispute->booking_id,
            ],
        );
    }
}
