<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Enums\LuggageStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Luggage;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────
        $sender = User::firstOrCreate(
            ['email' => 'sender@gpvalise.com'],
            [
                'first_name'        => 'Expéditeur',
                'last_name'         => 'Test',
                'password'          => Hash::make('password'),
                'role'              => UserRoleEnum::SENDER,
                'phone'             => '+212600000001',
                'email_verified_at' => now(),
                'verified_user'     => true,
            ]
        );

        $traveler = User::firstOrCreate(
            ['email' => 'traveler@gpvalise.com'],
            [
                'first_name'        => 'Voyageur',
                'last_name'         => 'Test',
                'password'          => Hash::make('password'),
                'role'              => UserRoleEnum::TRAVELER,
                'phone'             => '+212600000002',
                'email_verified_at' => now(),
                'verified_user'     => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@gpvalise.com'],
            [
                'first_name'        => 'Admin',
                'last_name'         => 'GPValise',
                'password'          => Hash::make('password'),
                'role'              => UserRoleEnum::ADMIN,
                'phone'             => '+212600000003',
                'email_verified_at' => now(),
                'verified_user'     => true,
            ]
        );

        // ── Trips ─────────────────────────────────────────────────────────────
        // Capacité 60kg pour absorber tous les bookings demo sans overflow
        $tripCasaParis = Trip::factory()->create([
            'user_id'      => $traveler->id,
            'departure'    => 'Casablanca',
            'destination'  => 'Paris',
            'capacity'     => 60000,
            'price_per_kg' => 800,
            'currency'     => \App\Enums\CurrencyEnum::MAD->value,  // ← ajouter
            'date'         => now()->addDays(30),
        ]);

        $tripDakarAbidjan = Trip::factory()->create([
            'user_id'      => $traveler->id,
            'departure'    => 'Dakar',
            'destination'  => 'Abidjan',
            'capacity'     => 40000,
            'price_per_kg' => 600,
            'currency'     => \App\Enums\CurrencyEnum::XOF->value,  // ← ajouter
            'date'         => now()->addDays(20),
        ]);

        // ── Helpers ───────────────────────────────────────────────────────────
        $makeLuggage = function (User $user, Trip $trip, LuggageStatusEnum $status) {
            return Luggage::factory()->create([
                'user_id'       => $user->id,
                'trip_id'       => $trip->id,
                'status'        => $status,
                'pickup_city'   => $trip->departure,
                'delivery_city' => $trip->destination,
                'pickup_date'   => $trip->date,
                'delivery_date' => $trip->date->copy()->addDay(),
            ]);
        };

        $recipient = [
            'recipient_name'  => 'Fatou Diallo',
            'recipient_phone' => '+221771234567',
            'recipient_email' => 'fatou.diallo@demo.com',
        ];

        // ── Bookings — Casablanca → Paris ─────────────────────────────────────
        // Total actif (EN_PAIEMENT + CONFIRMEE + EN_TRANSIT) = 5+8+3 = 16kg < 60kg ✅

        // EN_PAIEMENT — paiement en attente
        $bookingEnPaiement = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'             => BookingStatusEnum::EN_PAIEMENT,
            'payment_expires_at' => now()->addMinutes(30),
            ...$recipient,
        ]);
        $bookingEnPaiement->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripCasaParis, LuggageStatusEnum::RESERVEE)->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 5000,
            'price'       => 4000,
        ]);

        // CONFIRMEE — paiement reçu, en attente de remise
        $bookingConfirmee = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::CONFIRMEE,
            'confirmed_at' => now()->subDays(2),
            ...$recipient,
        ]);
        $bookingConfirmee->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripCasaParis, LuggageStatusEnum::RESERVEE)->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 8000,
            'price'       => 6400,
        ]);

        // EN_TRANSIT — colis remis, QR envoyé au destinataire
        $bookingEnTransit = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'            => BookingStatusEnum::EN_TRANSIT,
            'confirmed_at'      => now()->subDays(3),
            'handed_over_at'    => now()->subHours(6),
            'delivery_code'     => '482917',
            'delivery_qr_token' => Str::uuid()->toString(),
            ...$recipient,
        ]);
        $bookingEnTransit->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripCasaParis, LuggageStatusEnum::RESERVEE)->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 3000,
            'price'       => 2400,
        ]);

        // LIVREE — destinataire a scanné le QR, escrow en cours
        // delivered_at J-2 → escrow_releasable_at J-2+48h = aujourd'hui ~maintenant
        $bookingLivree = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'               => BookingStatusEnum::LIVREE,
            'confirmed_at'         => now()->subDays(5),
            'handed_over_at'       => now()->subDays(4),
            'delivered_at'         => now()->subDays(2),
            'escrow_releasable_at' => now()->subDays(2)->addHours(48),
            ...$recipient,
        ]);
        $bookingLivree->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripCasaParis, LuggageStatusEnum::LIVREE)->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 10000,
            'price'       => 8000,
        ]);

        // REMBOURSEE
        $bookingRemboursee = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'        => BookingStatusEnum::REMBOURSEE,
            'confirmed_at'  => now()->subDays(8),
            'cancel_reason' => 'Remboursement suite à litige',
            'refund_rate'   => 100,
            ...$recipient,
        ]);
        $bookingRemboursee->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripCasaParis, LuggageStatusEnum::ANNULEE)->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 6000,
            'price'       => 4800,
        ]);

        // ── Bookings — Dakar → Abidjan ────────────────────────────────────────

        // EN_LITIGE
        $bookingLitige = Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'       => BookingStatusEnum::EN_LITIGE,
            'disputed_at'  => now()->subHours(3),
            'confirmed_at' => now()->subDays(4),
            ...$recipient,
        ]);
        $bookingLitige->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripDakarAbidjan, LuggageStatusEnum::RESERVEE)->id,
            'trip_id'     => $tripDakarAbidjan->id,
            'kg_reserved' => 7000,
            'price'       => 4200,
        ]);

        // TERMINE — timeline complète cohérente
        // delivered_at J-8 → escrow_releasable_at J-8+48h = J-6 → completed_at J-7 ✅
        $bookingTermine = Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'               => BookingStatusEnum::TERMINE,
            'confirmed_at'         => now()->subDays(10),
            'handed_over_at'       => now()->subDays(9),
            'delivered_at'         => now()->subDays(8),
            'escrow_releasable_at' => now()->subDays(8)->addHours(48), // = J-6
            'completed_at'         => now()->subDays(7),
            ...$recipient,
        ]);
        $bookingTermine->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripDakarAbidjan, LuggageStatusEnum::LIVREE)->id,
            'trip_id'     => $tripDakarAbidjan->id,
            'kg_reserved' => 12000,
            'price'       => 7200,
        ]);

        // ANNULE refund 100% — annulation >48h avant départ
        $bookingAnnule100 = Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'        => BookingStatusEnum::ANNULE,
            'confirmed_at'  => now()->subDays(5),
            'cancelled_at'  => now()->subDays(4),
            'cancel_reason' => 'Annulation par l\'expéditeur',
            'refund_rate'   => 100,
            ...$recipient,
        ]);
        $bookingAnnule100->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripDakarAbidjan, LuggageStatusEnum::ANNULEE)->id,
            'trip_id'     => $tripDakarAbidjan->id,
            'kg_reserved' => 4000,
            'price'       => 2400,
        ]);

        // ANNULE refund 70% — annulation <48h avant départ
        $bookingAnnule70 = Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'        => BookingStatusEnum::ANNULE,
            'confirmed_at'  => now()->subDays(2),
            'cancelled_at'  => now()->subHours(12),
            'cancel_reason' => 'Annulation par l\'expéditeur',
            'refund_rate'   => 70,
            ...$recipient,
        ]);
        $bookingAnnule70->bookingItems()->create([
            'luggage_id'  => $makeLuggage($sender, $tripDakarAbidjan, LuggageStatusEnum::ANNULEE)->id,
            'trip_id'     => $tripDakarAbidjan->id,
            'kg_reserved' => 3000,
            'price'       => 1800,
        ]);

        $this->command->info('✅ DemoDataSeeder — users + trips + bookings créés');
        $this->command->info('   sender@gpvalise.com   / password');
        $this->command->info('   traveler@gpvalise.com / password');
        $this->command->info('   admin@gpvalise.com    / password');
    }
}
