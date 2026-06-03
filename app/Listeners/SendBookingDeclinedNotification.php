<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingDeclined;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBookingDeclinedNotification implements ShouldQueue
{
    public function handle(BookingDeclined $event): void
    {
        // TODO: Mail sender — booking refusé par le voyageur
        // Mail::to($event->booking->user)->send(new BookingDeclinedMail($event->booking));
    }
}
