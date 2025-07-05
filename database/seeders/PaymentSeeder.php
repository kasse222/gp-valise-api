<?php

namespace Database\Seeders;

use App\Enums\CurrencyEnum;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::inRandomOrder()->take(50)->get();

        foreach ($bookings as $booking) {
            if ($booking->payment) continue;

            $status = fake()->randomElement(PaymentStatusEnum::cases());
            $method = fake()->randomElement(PaymentMethodEnum::cases());

            Payment::create([
                'user_id'           => $booking->user_id,
                'booking_id'        => $booking->id,
                'amount'            => fake()->randomFloat(2, 20, 500),
                'method'            => $method->value,
                'currency' => fake()->randomElement(CurrencyEnum::values()),
                'payment_reference' => strtoupper(Str::random(12)),
                'paid_at'           => $status->isSuccess() ? now()->subDays(rand(0, 15)) : null,
            ]);
        }
    }
}
