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

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────


        $sender = User::firstOrCreate(
            ['email' => 'sender@gpvalise.com'],
            [
                'verified_user'     => true,
                'email_verified_at' => now(),
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
                'verified_user'     => true,
                'email_verified_at' => now(),
                'first_name'        => 'Voyageur',
                'last_name'         => 'Test',
                'password'          => Hash::make('password'),
                'role'              => UserRoleEnum::TRAVELER,
                'phone'             => '+212600000002',
                'email_verified_at' => now(),
                'verified_user'     => true,
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@gpvalise.com'],
            [
                'verified_user'     => true,
                'email_verified_at' => now(),
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
        $tripCasaParis = Trip::factory()->create([
            'user_id'     => $traveler->id,
            'departure'   => 'Casablanca',
            'destination' => 'Paris',
            'capacity'    => 20000,
            'price_per_kg' => 800,
        ]);

        $tripDakarAbidjan = Trip::factory()->create([
            'user_id'     => $traveler->id,
            'departure'   => 'Dakar',
            'destination' => 'Abidjan',
            'capacity'    => 15000,
            'price_per_kg' => 600,
        ]);

        // ── Bookings sender ───────────────────────────────────────────────────
        Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::EN_PAIEMENT,
            'payment_expires_at' => now()->addMinutes(30),
        ]);
        $luggage = Luggage::factory()->for($sender)->create([
            'status'       => LuggageStatusEnum::RESERVEE,
            'pickup_city'  => 'Casablanca',
            'delivery_city' => 'Paris',
        ]);
        Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::CONFIRMEE,
            'confirmed_at' => now()->subDays(2),
        ]);
        $luggage = Luggage::factory()->for($sender)->create([
            'status'       => LuggageStatusEnum::RESERVEE,
            'pickup_city'  => 'Casablanca',
            'delivery_city' => 'Paris',
        ]);

        Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::LIVREE,
            'confirmed_at' => now()->subDays(5),
            'completed_at' => now()->subDays(1),
        ]);
        $luggage = Luggage::factory()->for($sender)->create([
            'status'       => LuggageStatusEnum::RESERVEE,
            'pickup_city'  => 'Casablanca',
            'delivery_city' => 'Paris',
        ]);
        Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'      => BookingStatusEnum::EN_LITIGE,
            'disputed_at' => now()->subHours(3),
            'confirmed_at' => now()->subDays(4),
        ]);
        $luggage = Luggage::factory()->for($sender)->create([
            'status'       => LuggageStatusEnum::RESERVEE,
            'pickup_city'  => 'Casablanca',
            'delivery_city' => 'Paris',
        ]);
        Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'      => BookingStatusEnum::TERMINE,
            'confirmed_at' => now()->subDays(10),
            'completed_at' => now()->subDays(7),
        ]);
        $luggage = Luggage::factory()->for($sender)->create([
            'status'       => LuggageStatusEnum::RESERVEE,
            'pickup_city'  => 'Casablanca',
            'delivery_city' => 'Paris',
        ]);

        Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'   => BookingStatusEnum::REMBOURSEE,
            'confirmed_at' => now()->subDays(8),
        ]);

        $luggage = Luggage::factory()->for($sender)->create([
            'status'       => LuggageStatusEnum::RESERVEE,
            'pickup_city'  => 'Casablanca',
            'delivery_city' => 'Paris',
        ]);
        // ── Bookings sender ───────────────────────────────────────────────────

        $bookingEnPaiement = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'             => BookingStatusEnum::EN_PAIEMENT,
            'payment_expires_at' => now()->addMinutes(30),
        ]);
        $bookingEnPaiement->bookingItems()->create([
            'luggage_id'  => Luggage::factory()->for($sender)->create([
                'status' => LuggageStatusEnum::RESERVEE,
                'pickup_city' => 'Casablanca',
                'delivery_city' => 'Paris',
            ])->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 5000,
            'price'       => 4000,
        ]);

        $bookingConfirmee = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::CONFIRMEE,
            'confirmed_at' => now()->subDays(2),
        ]);
        $bookingConfirmee->bookingItems()->create([
            'luggage_id'  => Luggage::factory()->for($sender)->create([
                'status' => LuggageStatusEnum::RESERVEE,
                'pickup_city' => 'Casablanca',
                'delivery_city' => 'Paris',
            ])->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 8000,
            'price'       => 6400,
        ]);

        $bookingLivree = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::LIVREE,
            'confirmed_at' => now()->subDays(5),
            'completed_at' => now()->subDays(1),
        ]);
        $bookingLivree->bookingItems()->create([
            'luggage_id'  => Luggage::factory()->for($sender)->create([
                'status' => LuggageStatusEnum::LIVREE,
                'pickup_city' => 'Casablanca',
                'delivery_city' => 'Paris',
            ])->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 10000,
            'price'       => 8000,
        ]);

        $bookingLitige = Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'       => BookingStatusEnum::EN_LITIGE,
            'disputed_at'  => now()->subHours(3),
            'confirmed_at' => now()->subDays(4),
        ]);
        $bookingLitige->bookingItems()->create([
            'luggage_id'  => Luggage::factory()->for($sender)->create([
                'status' => LuggageStatusEnum::RESERVEE,
                'pickup_city' => 'Dakar',
                'delivery_city' => 'Abidjan',
            ])->id,
            'trip_id'     => $tripDakarAbidjan->id,
            'kg_reserved' => 7000,
            'price'       => 4200,
        ]);

        $bookingTermine = Booking::factory()->for($sender)->for($tripDakarAbidjan)->create([
            'status'       => BookingStatusEnum::TERMINE,
            'confirmed_at' => now()->subDays(10),
            'completed_at' => now()->subDays(7),
        ]);
        $bookingTermine->bookingItems()->create([
            'luggage_id'  => Luggage::factory()->for($sender)->create([
                'status' => LuggageStatusEnum::LIVREE,
                'pickup_city' => 'Dakar',
                'delivery_city' => 'Abidjan',
            ])->id,
            'trip_id'     => $tripDakarAbidjan->id,
            'kg_reserved' => 12000,
            'price'       => 7200,
        ]);

        $bookingRemboursee = Booking::factory()->for($sender)->for($tripCasaParis)->create([
            'status'       => BookingStatusEnum::REMBOURSEE,
            'confirmed_at' => now()->subDays(8),
        ]);
        $bookingRemboursee->bookingItems()->create([
            'luggage_id'  => Luggage::factory()->for($sender)->create([
                'status' => LuggageStatusEnum::ANNULEE,
                'pickup_city' => 'Casablanca',
                'delivery_city' => 'Paris',
            ])->id,
            'trip_id'     => $tripCasaParis->id,
            'kg_reserved' => 6000,
            'price'       => 4800,
        ]);

        $this->command->info('✅ DemoDataSeeder — users + trips + bookings créés');
        $this->command->info("   sender@gpvalise.com   / password");
        $this->command->info("   traveler@gpvalise.com / password");
        $this->command->info("   admin@gpvalise.com    / password");
    }
}
