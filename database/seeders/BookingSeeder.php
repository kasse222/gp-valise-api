<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Enums\BookingStatusEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Récupère les utilisateurs expéditeurs (SENDER)
        $senders = User::where('role', \App\Enums\UserRoleEnum::SENDER->value)->get();
        $trips = Trip::all();

        if ($senders->isEmpty() || $trips->isEmpty()) {
            $this->command->warn("⚠ BookingSeeder : Aucun sender ou trip trouvé.");
            return;
        }

        // On crée ~1000 réservations (selon le nombre de senders)
        $bookingsToCreate = 1000;

        DB::transaction(function () use ($senders, $trips, $bookingsToCreate) {
            for ($i = 0; $i < $bookingsToCreate; $i++) {
                $sender = $senders->random();
                $trip   = $trips->random();
                $status = fake()->randomElement(BookingStatusEnum::cases());

                // Détermination des timestamps associés au statut
                $confirmedAt = $status === BookingStatusEnum::CONFIRMEE ? now()->subDays(rand(1, 5)) : null;
                $completedAt = $status === BookingStatusEnum::TERMINE ? now()->subDay() : null;
                $cancelledAt = $status === BookingStatusEnum::ANNULE ? now()->subDays(rand(2, 10)) : null;

                Booking::create([
                    'user_id'       => $sender->id,
                    'trip_id'       => $trip->id,
                    'status'        => $status->value,
                    'comment'       => fake()->optional()->sentence(),
                    'confirmed_at'  => $confirmedAt,
                    'completed_at'  => $completedAt,
                    'cancelled_at'  => $cancelledAt,
                ]);
            }
        });

        $this->command->info("✔ BookingSeeder : $bookingsToCreate réservations générées.");
    }
}
