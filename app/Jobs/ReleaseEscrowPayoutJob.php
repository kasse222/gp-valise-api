<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Transaction\CreatePayoutTransaction;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class ReleaseEscrowPayoutJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly Booking $booking,
    ) {
        $this->onQueue('high');
    }

    public function handle(CreatePayoutTransaction $action): void
    {
        if (! $this->booking->fresh()->isEscrowReleasable()) {
            Log::info('ReleaseEscrowPayoutJob: escrow non libérable, ignoré', [
                'booking_id' => $this->booking->id,
            ]);
            return;
        }

        try {
            $action->execute($this->booking->fresh());
        } catch (ValidationException $e) {
            Log::channel('stack')->critical(
                'ReleaseEscrowPayoutJob: payout échoué',
                [
                    'booking_id' => $this->booking->id,
                    'error'      => $e->getMessage(),
                ]
            );

            dispatch(new SendSlackAlert(
                'Escrow payout échoué',
                ['booking_id' => $this->booking->id, 'error' => $e->getMessage()],
                'critical'
            ));
        }
    }
}
