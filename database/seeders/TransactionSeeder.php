<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Booking;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::inRandomOrder()->take(10)->get();
        $bookings = Booking::inRandomOrder()->take(20)->get();

        foreach ($bookings as $booking) {
            Transaction::create([
                'user_id'       => $booking->user_id,
                'booking_id'    => $booking->id,
                'amount'        => fake()->randomFloat(2, 15, 200),
                'currency'      => 'EUR',
                'status'        => fake()->randomElement(TransactionStatusEnum::values()),
                'method'        => fake()->randomElement(PaymentMethodEnum::values()),
                'processed_at'  => Carbon::now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
