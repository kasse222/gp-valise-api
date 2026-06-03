<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBookingApprovedNotification implements ShouldQueue
{
    public function handle(BookingApproved $event): void
    {
        // TODO: Mail sender — booking approuvé, procéder au paiement
        // Mail::to($event->booking->user)->send(new BookingApprovedMail($event->booking));
    }
}
