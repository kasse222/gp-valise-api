<?php

namespace Database\Seeders;

use App\Enums\CurrencyEnum;
use App\Models\Transaction;
use App\Models\Booking;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // 🎯 On cible les bookings confirmés ou terminés
        $bookings = Booking::whereIn('status', [
            \App\Enums\BookingStatusEnum::CONFIRMEE->value,
            \App\Enums\BookingStatusEnum::TERMINE->value,
        ])->get();

        if ($bookings->isEmpty()) {
            $this->command->warn('❌ Aucun booking confirmé ou terminé → Transactions ignorées.');
            return;
        }

        $nb = 0;

        foreach ($bookings as $booking) {
            Transaction::create([
                'user_id'      => $booking->user_id,
                'booking_id'   => $booking->id,
                'amount'       => fake()->randomFloat(2, 15, 200),
                'currency' => CurrencyEnum::default()->value,
                'status'       => fake()->randomElement(TransactionStatusEnum::values()),
                'method'       => fake()->randomElement(PaymentMethodEnum::values()),
                'processed_at' => Carbon::parse($booking->created_at)->addDays(rand(0, 5)),
            ]);
            $nb++;
        }

        $this->command->info("✔ $nb transactions créées avec succès.");
    }
}
