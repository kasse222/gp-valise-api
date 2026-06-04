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

class BookingApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre réservation a été acceptée — vous pouvez procéder au paiement',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.approved',
            with: [
                'booking'     => $this->booking,
                'trip'        => $this->booking->trip,
                'paymentUrl'  => config('app.frontend_url') . '/sender/bookings/' . $this->booking->id,
            ],
        );
    }
}
