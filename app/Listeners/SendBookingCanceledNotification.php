<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCanceled;
use App\Mail\Booking\BookingCanceledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingCanceledNotification implements ShouldQueue
{
    public function handle(BookingCanceled $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip']);

        Mail::to($booking->user->email)
            ->queue(new BookingCanceledMail($booking));
    }
}
