<?php

namespace Database\Seeders;

use App\Enums\CurrencyEnum;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;

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
                'currency' => CurrencyEnum::default()->value,
                'currency'          => fake()->randomElement(CurrencyEnum::values()),
                //  'payment_reference' => fake()->uuid(),
                'paid_at'           => $status->isSuccess() ? now()->subDays(rand(0, 15)) : null,
            ]);
        }
    }
}
