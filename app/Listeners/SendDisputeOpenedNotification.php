<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingDisputed;
use App\Mail\Booking\DisputeOpenedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendDisputeOpenedNotification implements ShouldQueue
{
    public function handle(BookingDisputed $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip', 'trip.user']);

        // Notifier sender + traveler
        Mail::to($booking->user->email)
            ->queue(new DisputeOpenedMail($booking));

        Mail::to($booking->trip->user->email)
            ->queue(new DisputeOpenedMail($booking));
    }
}
