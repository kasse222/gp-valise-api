<?php

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    private const STATUSES = [
        BookingStatusEnum::EN_PAIEMENT,
        BookingStatusEnum::CONFIRMEE,
        BookingStatusEnum::EN_TRANSIT,
        BookingStatusEnum::LIVREE,
        BookingStatusEnum::TERMINE,
        BookingStatusEnum::ANNULE,
        BookingStatusEnum::EXPIREE,
        BookingStatusEnum::REMBOURSEE,
    ];

    public function run(): void
    {
        $senders = User::where('role', \App\Enums\UserRoleEnum::SENDER->value)->get();
        $trips   = Trip::all();

        if ($senders->isEmpty() || $trips->isEmpty()) {
            $this->command->warn('⚠ BookingSeeder : Aucun sender ou trip trouvé.');
            return;
        }

        $bookingsToCreate = 1000;

        DB::transaction(function () use ($senders, $trips, $bookingsToCreate) {
            for ($i = 0; $i < $bookingsToCreate; $i++) {
                $sender = $senders->random();
                $trip   = $trips->random();
                $status = fake()->randomElement(self::STATUSES);

                Booking::create([
                    'user_id'         => $sender->id,
                    'trip_id'         => $trip->id,
                    'status'          => $status->value,
                    'comment'         => fake()->optional()->sentence(),
                    'recipient_name'  => fake()->name(),
                    'recipient_phone' => fake()->phoneNumber(),
                    'recipient_email' => fake()->safeEmail(),
                    ...$this->timestampsFor($status),
                ]);
            }
        });

        $this->command->info("✔ BookingSeeder : $bookingsToCreate réservations générées.");
    }

    private function timestampsFor(BookingStatusEnum $status): array
    {
        return match ($status) {
            BookingStatusEnum::EN_PAIEMENT => [
                'payment_expires_at' => now()->addMinutes(15),
            ],
            BookingStatusEnum::CONFIRMEE => [
                'confirmed_at' => now()->subDays(rand(1, 3)),
            ],
            BookingStatusEnum::EN_TRANSIT => [
                'confirmed_at'      => now()->subDays(2),
                'handed_over_at'    => now()->subHours(rand(1, 10)),
                'delivery_code'     => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'delivery_qr_token' => Str::uuid()->toString(),
            ],
            BookingStatusEnum::LIVREE => [
                'confirmed_at'         => now()->subDays(3),
                'handed_over_at'       => now()->subDays(2),
                'delivered_at'         => now()->subHours(rand(1, 47)),
                'escrow_releasable_at' => now()->addHours(rand(1, 47)),
                'delivery_code'        => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'delivery_qr_token'    => Str::uuid()->toString(),
            ],
            BookingStatusEnum::TERMINE => [
                'confirmed_at'         => now()->subDays(5),
                'handed_over_at'       => now()->subDays(4),
                'delivered_at'         => now()->subDays(3),
                'escrow_releasable_at' => now()->subDays(1),
                'completed_at'         => now()->subHours(rand(1, 12)),
            ],
            BookingStatusEnum::ANNULE => [
                'cancelled_at'  => now()->subDays(rand(1, 10)),
                'cancel_reason' => fake()->randomElement([
                    "Annulation par l'expéditeur",
                    'Annulation par le voyageur',
                    'Annulation administrative',
                ]),
                'refund_rate' => fake()->randomElement([100, 70, 0]),
            ],
            BookingStatusEnum::EXPIREE => [
                'payment_expires_at' => now()->subMinutes(rand(5, 60)),
                'expired_at'         => now()->subMinutes(rand(1, 30)),
            ],
            BookingStatusEnum::REMBOURSEE => [
                'confirmed_at'  => now()->subDays(rand(2, 7)),
                'cancelled_at'  => now()->subDays(1),
                'cancel_reason' => 'Remboursement suite à litige',
                'refund_rate'   => 100,
            ],
            default => [],
        };
    }
}
