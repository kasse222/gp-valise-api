<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Enums\DisputeStatusEnum;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Database\Seeder;

class DisputeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $booking = Booking::where('status', BookingStatusEnum::EN_LITIGE)->first();
        $sender  = User::where('email', 'sender@gpvalise.com')->firstOrFail();
        $admin   = User::where('email', 'admin@gpvalise.com')->firstOrFail();

        if (! $booking) {
            $this->command->warn('Aucun booking EN_LITIGE trouvé.');
            return;
        }

        Dispute::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'opened_by'   => $sender->id,
                'status'      => DisputeStatusEnum::UNDER_REVIEW,
                'reason'      => 'Colis non reçu malgré confirmation de livraison.',
                'assigned_to' => $admin->id,
            ]
        );

        $this->command->info("✅ Dispute créé pour booking #{$booking->id}");
    }
}
