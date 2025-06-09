<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On cible les bookings déjà créés (réservations valides)
        $bookings = Booking::all();

        foreach ($bookings as $booking) {
            // 80% des bookings ont un paiement associé
            if (rand(1, 100) <= 80) {
                Payment::factory()->create([
                    'booking_id' => $booking->id,
                    'amount'     => rand(20, 100), // montant fictif
                    'status'     => rand(0, 1) ? 'paye' : 'en_attente',
                    'provider'   => fake()->randomElement(['stripe', 'paypal', 'cash']),
                    'reference'  => fake()->uuid(),
                    'paid_at'    => now()->subDays(rand(0, 10)),
                ]);
            }
        }
    }
}
