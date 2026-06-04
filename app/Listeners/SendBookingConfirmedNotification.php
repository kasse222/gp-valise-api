<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\Booking\BookingConfirmedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedNotification implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip']);

        Mail::to($booking->user->email)
            ->queue(new BookingConfirmedMail($booking));
    }
}
