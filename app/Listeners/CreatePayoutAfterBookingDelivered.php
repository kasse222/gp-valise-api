<?php

namespace App\Listeners;

use App\Actions\Transaction\CreatePayoutTransaction;
use App\Events\BookingDelivered;
use App\Jobs\SendSlackAlert;
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
            Log::channel('stack')->critical(
                'PAYOUT ÉCHOUÉ après livraison booking',
                ['booking_id' => $event->booking->id, 'error' => $e->getMessage()]
            );

            dispatch(new SendSlackAlert(
                'Payout échoué après livraison',
                ['booking_id' => $event->booking->id, 'error' => $e->getMessage()],
                'critical'
            ));
        }
    }
}
