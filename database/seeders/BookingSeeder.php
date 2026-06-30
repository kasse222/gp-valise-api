<?php

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Transaction;
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

    // Statuts pour lesquels un paiement (CHARGE) a réellement été effectué
    private const STATUSES_WITH_CHARGE = [
        BookingStatusEnum::CONFIRMEE,
        BookingStatusEnum::EN_TRANSIT,
        BookingStatusEnum::LIVREE,
        BookingStatusEnum::TERMINE,
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
        $chargesCreated   = 0;
        $payoutsCreated   = 0;
        $refundsCreated   = 0;

        DB::transaction(function () use (
            $senders,
            $trips,
            $bookingsToCreate,
            &$chargesCreated,
            &$payoutsCreated,
            &$refundsCreated
        ) {
            for ($i = 0; $i < $bookingsToCreate; $i++) {
                $sender = $senders->random();
                $trip   = $trips->random();
                $status = fake()->randomElement(self::STATUSES);

                $amount   = fake()->numberBetween(2000, 15000); // en centimes / plus petite unité
                $currency = $trip->currency ?? CurrencyEnum::XOF->value;

                $booking = Booking::create([
                    'user_id'         => $sender->id,
                    'trip_id'         => $trip->id,
                    'status'          => $status->value,
                    'comment'         => fake()->optional()->sentence(),
                    'recipient_name'  => fake()->name(),
                    'recipient_phone' => fake()->phoneNumber(),
                    'recipient_email' => fake()->safeEmail(),
                    ...$this->timestampsFor($status),
                ]);

                // ── Transaction CHARGE — dès qu'un paiement a logiquement eu lieu ──
                if (in_array($status, self::STATUSES_WITH_CHARGE, true)) {
                    $charge = Transaction::create([
                        'user_id'                 => $sender->id,
                        'booking_id'              => $booking->id,
                        'type'                    => TransactionTypeEnum::CHARGE->value,
                        'amount'                  => $amount,
                        'currency'                => $currency,
                        'method'                  => fake()->randomElement([
                            PaymentMethodEnum::CARD->value,
                            PaymentMethodEnum::MOBILE_MONEY->value,
                        ]),
                        'status'                  => TransactionStatusEnum::COMPLETED->value,
                        'provider_transaction_id' => 'seed-charge-' . Str::random(10),
                        'processed_at'            => now()->subDays(rand(1, 10)),
                    ]);
                    $chargesCreated++;

                    // TERMINE = cycle complet → un PAYOUT a déjà été versé au voyageur
                    if ($status === BookingStatusEnum::TERMINE) {
                        Transaction::create([
                            'user_id'                 => $trip->user_id,
                            'booking_id'              => $booking->id,
                            'type'                    => TransactionTypeEnum::PAYOUT->value,
                            'amount'                  => (int) round($amount * 0.85), // commission ~15%
                            'currency'                => $currency,
                            'method'                  => $charge->method,
                            'status'                  => TransactionStatusEnum::COMPLETED->value,
                            'provider_transaction_id' => 'seed-payout-' . Str::random(10),
                            'processed_at'            => now()->subDays(rand(1, 5)),
                        ]);
                        $payoutsCreated++;
                    }

                    // REMBOURSEE = la charge a été remboursée
                    if ($status === BookingStatusEnum::REMBOURSEE) {
                        Transaction::create([
                            'user_id'                 => $sender->id,
                            'booking_id'              => $booking->id,
                            'type'                    => TransactionTypeEnum::REFUND->value,
                            'amount'                  => $amount,
                            'currency'                => $currency,
                            'method'                  => $charge->method,
                            'status'                  => TransactionStatusEnum::COMPLETED->value,
                            'provider_transaction_id' => 'seed-refund-' . Str::random(10),
                            'processed_at'            => now()->subDays(rand(1, 3)),
                        ]);
                        $refundsCreated++;
                    }
                }
            }
        });

        $this->command->info("✔ BookingSeeder : $bookingsToCreate réservations générées.");
        $this->command->info("  → $chargesCreated transactions CHARGE créées");
        $this->command->info("  → $payoutsCreated transactions PAYOUT créées (bookings TERMINE)");
        $this->command->info("  → $refundsCreated transactions REFUND créées (bookings REMBOURSEE)");
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
