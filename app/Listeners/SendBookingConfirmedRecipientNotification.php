<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\Booking\BookingConfirmedRecipientMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedRecipientNotification implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip', 'bookingItems']);

        if (! $booking->recipient_email) {
            return;
        }

        Mail::to($booking->recipient_email)
            ->queue(new BookingConfirmedRecipientMail($booking));
    }
}
