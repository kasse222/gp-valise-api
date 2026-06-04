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

class BookingCanceledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réservation annulée',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.canceled',
            with: [
                'booking'   => $this->booking,
                'trip'      => $this->booking->trip,
                'searchUrl' => config('app.frontend_url') . '/trips',
            ],
        );
    }
}
