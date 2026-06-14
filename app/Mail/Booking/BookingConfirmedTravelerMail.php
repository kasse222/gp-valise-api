<?php

declare(strict_types=1);

namespace App\Mail\Booking;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedTravelerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📦 Nouveau colis à transporter — Réservation #{$this->booking->id}",
        );
    }

    public function content(): Content
    {
        $trip    = $this->booking->trip;
        $sender  = $this->booking->user;
        $totalKg = $this->booking->bookingItems->sum('kg_reserved') / 1000;

        return new Content(
            markdown: 'emails.booking.confirmed-traveler',
            with: [
                'booking'      => $this->booking,
                'trip'         => $trip,
                'sender'       => $sender,
                'totalKg'      => $totalKg,
                'dashboardUrl' => config('app.frontend_url') . '/dashboard',
            ],
        );
    }
}
