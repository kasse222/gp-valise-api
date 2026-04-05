<?php

namespace App\Listeners;

use App\Actions\Transaction\CreatePayoutTransaction;
use App\Events\BookingDelivered;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreatePayoutAfterBookingDelivered
{
    public function __construct(
        private CreatePayoutTransaction $action
    ) {}

    public function handle(BookingDelivered $event): void
    {
        try {
            $this->action->execute($event->booking);
        } catch (ValidationException $e) {
            Log::warning('booking.payout.skipped', [
                'booking_id' => $event->booking->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
