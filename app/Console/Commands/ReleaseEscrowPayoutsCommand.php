<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Booking\ReleaseEscrowBatch;
use Illuminate\Console\Command;

final class ReleaseEscrowPayoutsCommand extends Command
{
    protected $signature   = 'escrow:release-payouts';
    protected $description = 'Scanne les bookings dont l\'escrow est libérable et dispatche les payouts';

    public function handle(ReleaseEscrowBatch $batch): int
    {
        $count = $batch->execute();

        $this->info("✔ {$count} payout(s) escrow dispatché(s).");

        return Command::SUCCESS;
    }
}
