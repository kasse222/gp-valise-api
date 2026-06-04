<?php

declare(strict_types=1);

namespace App\Mail\Booking;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre réservation a expiré',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.expired',
            with: [
                'booking'   => $this->booking,
                'searchUrl' => config('app.frontend_url') . '/trips',
            ],
        );
    }
}
