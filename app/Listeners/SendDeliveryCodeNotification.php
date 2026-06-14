<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingHandedOver;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Envoie le QR code et le code secret au destinataire du colis.
 * Déclenché lors de la remise physique (CONFIRMEE → EN_TRANSIT).
 *
 * TODO : implémenter l'envoi email + SMS via Resend / Twilio.
 */
class SendDeliveryCodeNotification implements ShouldQueue
{
    public function handle(BookingHandedOver $event): void
    {
        $booking = $event->booking;

        // Email destinataire avec QR token + code secret
        // Mail::to($booking->recipient_email)
        //     ->send(new DeliveryCodeMail($booking));
    }
}
