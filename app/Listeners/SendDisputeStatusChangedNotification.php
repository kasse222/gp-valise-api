<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeStatusChanged;
use App\Mail\Dispute\DisputeStatusChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendDisputeStatusChangedNotification implements ShouldQueue
{
    public function handle(DisputeStatusChanged $event): void
    {
        $dispute = $event->dispute->loadMissing(['booking.user', 'booking.trip.user']);

        Mail::to($dispute->booking->user->email)
            ->queue(new DisputeStatusChangedMail($dispute));

        Mail::to($dispute->booking->trip->user->email)
            ->queue(new DisputeStatusChangedMail($dispute));
    }
}
