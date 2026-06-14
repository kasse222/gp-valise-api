<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\Booking\BookingConfirmedTravelerMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedTravelerNotification implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip.user']);

        $traveler = $booking->trip?->user;

        if (! $traveler?->email) {
            return;
        }

        Mail::to($traveler->email)
            ->queue(new BookingConfirmedTravelerMail($booking));
    }
}
