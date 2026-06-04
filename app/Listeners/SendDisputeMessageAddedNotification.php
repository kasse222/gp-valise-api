<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeMessageAdded;
use App\Mail\Dispute\DisputeMessageAddedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendDisputeMessageAddedNotification implements ShouldQueue
{
    public function handle(DisputeMessageAdded $event): void
    {
        $message = $event->message->loadMissing([
            'dispute.booking.user',
            'dispute.booking.trip.user',
            'author',
        ]);
        $booking = $message->dispute->booking;

        // Notifier l'autre partie (pas l'auteur du message)
        $recipients = collect([
            $booking->user,
            $booking->trip->user,
        ])->filter(fn($user) => $user->id !== $message->author_id);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)
                ->queue(new DisputeMessageAddedMail($message));
        }
    }
}
