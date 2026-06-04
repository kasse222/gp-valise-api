<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingDelivered;
use App\Mail\Booking\BookingDeliveredMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingDeliveredNotification implements ShouldQueue
{
    public function handle(BookingDelivered $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip']);

        Mail::to($booking->user->email)
            ->queue(new BookingDeliveredMail($booking));
    }
}
