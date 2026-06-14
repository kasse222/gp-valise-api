<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingHandedOver;
use App\Mail\Booking\DeliveryCodeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendDeliveryCodeNotification implements ShouldQueue
{
    public function handle(BookingHandedOver $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'trip']);

        // Email au destinataire du colis (pas au sender)
        Mail::to($booking->recipient_email)
            ->queue(new DeliveryCodeMail($booking));
    }
}
