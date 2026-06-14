<?php

declare(strict_types=1);

namespace App\Mail\Booking;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📦 Votre colis arrive — Code de réception #{$this->booking->id}",
        );
    }

    public function content(): Content
    {
        $trip = $this->booking->trip;

        return new Content(
            markdown: 'emails.booking.delivery-code',
            with: [
                'booking'      => $this->booking,
                'trip'         => $trip,
                'deliveryCode' => $this->booking->delivery_code,
                'qrToken'      => $this->booking->delivery_qr_token,
                'trackingUrl'  => config('app.frontend_url') . '/track/' . $this->booking->delivery_qr_token,
            ],
        );
    }
}
