<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingExpired;
use App\Mail\Booking\BookingExpiredMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingExpiredNotification implements ShouldQueue
{
    public function handle(BookingExpired $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip']);

        Mail::to($booking->user->email)
            ->queue(new BookingExpiredMail($booking));
    }
}
