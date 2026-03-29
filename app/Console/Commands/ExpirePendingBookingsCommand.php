<?php

namespace App\Console\Commands;

use App\Actions\Booking\RunExpirePendingBookingsBatch;
use Illuminate\Console\Command;

class ExpirePendingBookingsCommand extends Command
{
    protected $signature = 'bookings:expire-pending {--chunk=100}';
    protected $description = 'Expire les bookings en attente de paiement dont le délai est dépassé';

    public function handle(RunExpirePendingBookingsBatch $action): int
    {
        $result = $action->execute((int) $this->option('chunk'));

        $this->info(
            "RunExpirePendingBookingsBatch terminé. " .
                "Scannés: {$result['scanned']}, " .
                "Expirés: {$result['expired']}, " .
                "Ignorés: {$result['skipped']}, " .
                "Erreurs: {$result['failed']}"
        );

        return self::SUCCESS;
    }
}
