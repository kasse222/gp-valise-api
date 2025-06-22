<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use App\Enums\BookingStatusEnum;

class BookingStatusHistorySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', \App\Enums\UserRoleEnum::ADMIN->value)->first();

        Booking::all()->each(function ($booking) use ($admin) {
            $statuses = [
                BookingStatusEnum::EN_ATTENTE,
                BookingStatusEnum::EN_PAIEMENT,
                BookingStatusEnum::CONFIRMEE,
                BookingStatusEnum::LIVREE,
                BookingStatusEnum::TERMINE,
            ];

            $previous = null;
            foreach ($statuses as $status) {
                if ($previous && !$previous->canTransitionTo($status)) {
                    break; // transition invalide, on arrête là
                }

                BookingStatusHistory::create([
                    'booking_id' => $booking->id,
                    'old_status' => $previous?->value,
                    'new_status' => $status->value,
                    'changed_by' => $admin?->id,
                    'reason'     => fake()->sentence(),
                ]);

                $previous = $status;
            }
        });
    }
}
