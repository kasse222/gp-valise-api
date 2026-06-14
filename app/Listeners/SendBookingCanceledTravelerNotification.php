<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCanceled;
use App\Mail\Booking\BookingCanceledTravelerMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingCanceledTravelerNotification implements ShouldQueue
{
    public function handle(BookingCanceled $event): void
    {
        $booking  = $event->booking->loadMissing(['user', 'trip.user']);
        $traveler = $booking->trip?->user;

        if (! $traveler?->email) {
            return;
        }

        // Éviter de notifier le traveler s'il est lui-même à l'origine de l'annulation
        if (
            $booking->cancel_reason === 'Annulation par le voyageur'
            && $traveler->id === $booking->trip->user_id
        ) {
            return;
        }

        Mail::to($traveler->email)
            ->queue(new BookingCanceledTravelerMail($booking));
    }
}
