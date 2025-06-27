<?php

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingStatusHistorySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', \App\Enums\UserRoleEnum::ADMIN->value)->first();

        if (!$admin) {
            $this->command->warn('❗ Aucun admin trouvé. Seeder BookingStatusHistory annulé.');
            return;
        }

        $count = 0;

        Booking::all()->each(function ($booking) use ($admin, &$count) {
            if ($booking->status === BookingStatusEnum::TERMINE || $booking->status === BookingStatusEnum::REMBOURSEE) {
                return; // on ne génère pas d'historique pour les terminés/remboursés
            }

            $statuses = [
                BookingStatusEnum::EN_ATTENTE,
                BookingStatusEnum::EN_PAIEMENT,
                BookingStatusEnum::CONFIRMEE,
                BookingStatusEnum::LIVREE,
                BookingStatusEnum::TERMINE,
            ];

            $previous = null;
            $date = Carbon::now()->subDays(count($statuses)); // point de départ

            foreach ($statuses as $status) {
                if ($previous && !$previous->canTransitionTo($status)) {
                    break;
                }

                BookingStatusHistory::create([
                    'booking_id'  => $booking->id,
                    'old_status'  => $previous?->value,
                    'new_status'  => $status->value,
                    'changed_by'  => $admin->id,
                    'reason'      => fake()->sentence(),
                    'created_at'  => $date->copy(),
                    'updated_at'  => $date->copy(),
                ]);

                $previous = $status;
                $date->addDay();
                $count++;
            }
        });

        $this->command->info("✔ BookingStatusHistorySeeder terminé avec $count transitions générées.");
    }
}
