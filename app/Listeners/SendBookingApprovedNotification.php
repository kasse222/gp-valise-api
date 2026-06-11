<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingApproved;
use App\Mail\Booking\BookingApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingApprovedNotification implements ShouldQueue
{
    public function handle(BookingApproved $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip', 'items']);

        Mail::to($booking->user->email)
            ->queue(new BookingApprovedMail($booking));
    }
}
